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
        $em = self::getEntityManager();
        $all_podcasts = $em->createQuery('SELECT p, ps, pe FROM Entity\Podcast p
            JOIN p.sources ps
            JOIN ps.episodes pe
            WHERE p.is_approved = 1
            AND ps.is_active = 1')->execute();

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

        $db_stats = array(
            'record'    => $record->name,
            'updated'   => 0,
            'inserted'  => 0,
            'deleted'   => 0,
        );

        foreach($record->sources as $source)
        {
            if ($source->is_active)
            {
                $new_episodes = $source->process();

                if (empty($new_episodes))
                    continue;

                // Reconcile differences.
                $existing_episodes = array();

                foreach($source->episodes as $episode)
                {
                    // Remove duplicate episode.
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
                        $episode->source = $source;
                        $episode->podcast = $record;

                        // Preload banner URL if specified, and if episode is new enough.
                        if ($ep_info['banner_url'] && $ep_info['timestamp'] > time()-86400*14)
                            PodcastEpisode::getEpisodeRotatorUrl($ep_info, $record, $source);
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

            }
            else
            {
                foreach($source->episodes as $episode)
                    $em->remove($episode);
            }

            $em->flush();
        }

        Debug::print_r($db_stats);
        return true;
    }

    public static function getEntityManager()
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('em');
    }
}