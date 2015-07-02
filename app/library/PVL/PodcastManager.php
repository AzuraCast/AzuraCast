<?php
namespace PVL;

use Entity\Podcast;
use Entity\PodcastEpisode;
use PVL\Debug;

class PodcastManager
{
    public static function run()
    {
        // Pull podcast news.
        $all_podcasts = Podcast::getRepository()->findBy(array('is_approved' => 1));

        foreach($all_podcasts as $record)
        {
            Debug::startTimer('Process podcast: '.$record->name);

            self::processPodcast($record);

            Debug::endTimer('Process podcast: '.$record->name);
        }
        return true;
    }

    public static function processPodcast(Podcast $record)
    {
        $em = self::getEntityManager();

        $social_fields = Podcast::getSocialTypes();
        $db_stats = array(
            'record'    => $record->name,
            'updated'   => 0,
            'inserted'  => 0,
            'deleted'   => 0,
        );

        // Pull new records.
        $new_episodes = array();

        foreach($record->sources as $source)
        {
            $source_episodes = $source->process();

            if (!empty($source_episodes))
                $new_episodes = array_merge($new_episodes, $source_episodes);
        }

        if (empty($new_episodes))
            return false;

        // Reconcile differences.
        $existing_episodes = array();

        foreach($record->episodes as $episode)
        {
            if (isset($existing_episodes[$episode->guid]))
            {
                $db_stats['deleted']++;
                $em->remove($episode);
            }
            else
            {
                $existing_episodes[$episode->guid] = $episode;
            }
        }

        foreach($new_episodes as $ep_guid => $ep_info)
        {
            if (isset($existing_episodes[$ep_guid]))
            {
                $db_stats['updated']++;
                $episode = $existing_episodes[$ep_guid];
            }
            else
            {
                $db_stats['inserted']++;
                $episode = new PodcastEpisode;
                $episode->podcast = $record;
            }

            $episode->fromArray($ep_info);
            $em->persist($episode);

            unset($existing_episodes[$ep_guid]);
        }

        foreach($existing_episodes as $ep_guid => $ep_to_remove)
        {
            $db_stats['deleted']++;
            $em->remove($ep_to_remove);
        }

        $em->flush();

        Debug::print_r($db_stats);
        return true;
    }



    public static function getEntityManager()
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('em');
    }
}