<?php
namespace Modules\Api\Controllers;

use \Entity\Station;
use \Entity\StationRequest;

class RequestsController extends BaseController
{
    public function listAction()
    {
        $station = $this->_getStation();

        if (!$station)
            return $this->returnError('Station not found!');

        $requestable_media = $this->em->createQuery('SELECT sm, s, sp 
            FROM Entity\StationMedia sm JOIN sm.song s LEFT JOIN sm.playlists sp
            WHERE sm.station_id = :station_id AND sp.id IS NOT NULL')
            ->setParameter('station_id', $station->id)
            ->getArrayResult();

        $result = array();

        foreach($requestable_media as $media_row)
        {
            $result_row = array(
                'song' => \Entity\Song::api($media_row['song']),
                'request_song_id' => $media_row['id'],
                'request_url' => $this->url->routeFromHere(['action' => 'submit', 'song_id' => $media_row['id']]),
            );
            $result[] = $result_row;
        }

        // Handle Bootgrid-style iteration through result
        if (!empty($_REQUEST['current']))
        {
            // Flatten the results array for bootgrid.
            foreach($result as &$row)
            {
                foreach($row['song'] as $song_key => $song_val)
                    $row['song_'.$song_key] = $song_val;
            }

            // Example from bootgrid docs:
            // current=1&rowCount=10&sort[sender]=asc&searchPhrase=&id=b0df282a-0d67-40e5-8558-c9e93b7befed

            // Apply sorting, limiting and searching.
            $search_phrase = trim($_REQUEST['searchPhrase']);

            if (!empty($search_phrase))
            {
                $result = array_filter($result, function($row) use($search_phrase) {
                    $search_fields = array('song_title', 'song_artist');

                    foreach($search_fields as $field_name)
                    {
                        if (stripos($row[$field_name], $search_phrase) !== false)
                            return true;
                    }

                    return false;
                });
            }

            if (!empty($_REQUEST['sort']))
            {
                $sort_by = [];
                foreach ($_REQUEST['sort'] as $sort_key => $sort_direction)
                {
                    $sort_dir = (strtolower($sort_direction) == 'desc') ? \SORT_DESC : \SORT_ASC;
                    $sort_by[] = $sort_key;
                    $sort_by[] = $sort_dir;
                }
            }
            else
            {
                $sort_by = ['song_artist', \SORT_ASC, 'song_title', \SORT_ASC];
            }

            $result = \App\Utilities::array_order_by($result, $sort_by);

            $num_results = count($result);

            $page = @$_REQUEST['current'] ?: 1;
            $row_count = @$_REQUEST['rowCount'] ?: 15;

            $offset_start = ($page - 1) * $row_count;
            $return_result = array_slice($result, $offset_start, $row_count);

            return $this->renderJson(array(
                'current' => $page,
                'rowCount' => $row_count,
                'total' => $num_results,
                'rows' => $return_result,
            ));
        }

        return $this->returnSuccess($result);
    }

    public function submitAction()
    {
        $station = $this->_getStation();

        if (!$station)
            return $this->returnError('Station not found!');

        $song = $this->getParam('song_id');

        try
        {
            $this->em->getRepository(StationRequest::class)->submit($station, $song, $this->authenticate());

            return $this->returnSuccess('Request submitted successfully.');
        }
        catch(\App\Exception $e)
        {
            return $this->returnError($e->getMessage());
        }
    }

    protected function _getStation()
    {
        $station = $this->getParam('station');

        if (is_numeric($station))
        {
            $id = (int)$station;
            $record = $this->em->getRepository(Station::class)->find($id);
        }
        else
        {
            $record = $this->em->getRepository(Station::class)->findByShortCode($this->getParam('station'));
        }

        if (!($record instanceof Station) || $record->deleted_at)
            return null;

        return $record;
    }
}