<?php
namespace PVL;

use \Entity\Podcast;
use \Entity\PodcastEpisode;
use \Entity\Settings;

class PodcastManager
{
    public static function run()
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        $social_fields = Podcast::getSocialTypes();

        // Pull podcast news.
        $all_podcasts = $em->createQuery('SELECT p, pe FROM Entity\Podcast p LEFT JOIN p.episodes pe WHERE p.is_approved = 1 ORDER BY p.id ASC')
            ->execute();

        foreach($all_podcasts as $record)
        {
            $db_stats = array(
                'record'    => $record->name,
                'updated'   => 0,
                'inserted'  => 0,
                'deleted'   => 0,
            );

            // Pull new records.
            $new_episodes = array();

            foreach($social_fields as $field_key => $field_adapter)
            {
                if (empty($record[$field_key]) || !isset($field_adapter['adapter']))
                    continue;

                // Look for new news items.
                $class_name = '\\PVL\\NewsAdapter\\'.$field_adapter['adapter'];
                $news_items = $class_name::fetch($record[$field_key], $field_adapter['settings']);

                foreach((array)$news_items as $item)
                {
                    $guid = $item['guid'];

                    $new_episodes[$guid] = array(
                        'guid'      => $guid,
                        'timestamp' => $item['timestamp'],
                        'title'     => $item['title'],
                        'body'      => self::cleanUpText($item['body']),
                        'web_url'   => $item['web_url'],
                    );
                }
            }

            if (empty($new_episodes))
                continue;

            // Reconcile differences.
            $existing_episodes = array();

            foreach($record->episodes as $episode)
                $existing_episodes[$episode->guid] = $episode;

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

            \PVL\Debug::print_r($db_stats);
        }

        return true;
    }

    public static function cleanUpText($text)
    {
        $text = strip_tags($text);
        return trim($text);
    }
}