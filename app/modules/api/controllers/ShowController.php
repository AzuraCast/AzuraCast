<?php
use \Entity\Podcast;
use \Entity\PodcastEpisode;

class Api_ShowController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
        if ($this->_hasParam('id'))
        {
            $id = (int)$this->_getParam('id');

            $record = $this->em->createQuery('SELECT p, s, pe FROM Entity\Podcast p LEFT JOIN p.stations s LEFT JOIN p.episodes pe WHERE p.is_approved = 1 AND p.id = :id')
                ->setParameter('id', $id)
                ->execute();

            if ($record[0] instanceof Podcast)
            {
                $return = Podcast::api($record[0], TRUE);
                return $this->returnSuccess($return);
            }
            else
            {
                return $this->returnError('Show not found!');
            }
        }
        else
        {
            $records = $this->em->createQuery('SELECT p, s, pe FROM Entity\Podcast p LEFT JOIN p.stations s LEFT JOIN p.episodes pe WHERE p.is_approved = 1 ORDER BY p.name ASC')
                ->execute();

            $return = array();
            foreach($records as $record)
            {
                $return[] = Podcast::api($record, TRUE);
            }

            return $this->returnSuccess($return);
        }
    }

    public function latestAction()
    {
        try
        {
            $latest_shows = Podcast::fetchLatest();

            $return = array();
            foreach((array)$latest_shows as $show_info)
            {
                $return_row = Podcast::api($show_info['record'], FALSE);

                foreach((array)$show_info['episodes'] as $ep_row)
                    $return_row['episodes'][] = PodcastEpisode::api($ep_row);

                $return[] = $return_row;
            }

            return $this->returnSuccess($return);
        }
        catch(\Exception $e)
        {
            return $this->returnError($e->getMessage());
        }
    }
}