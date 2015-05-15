<?php
namespace Modules\Api\Controllers;

use \Entity\Podcast;
use \Entity\PodcastEpisode;

class ShowController extends BaseController
{
    public function indexAction()
    {
        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');

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
            $return = \DF\Cache::get('api_shows');

            if (!$return)
            {
                $records = $this->em->createQuery('SELECT p, s, pe FROM Entity\Podcast p LEFT JOIN p.stations s LEFT JOIN p.episodes pe WHERE p.is_approved = 1 ORDER BY p.name ASC')
                    ->getArrayResult();

                $return = array();
                foreach ($records as $record)
                {
                    $return[] = Podcast::api($record, 10);
                }

                \DF\Cache::set($return, 'api_shows', array(), 60);
            }

            return $this->returnSuccess($return);
        }
    }

    public function listAction()
    {
        return $this->indexAction();
    }

    public function viewAction()
    {
        return $this->indexAction();
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