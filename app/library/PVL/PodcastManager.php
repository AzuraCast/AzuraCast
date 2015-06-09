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
            self::processPodcast($record);
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
                    'summary'   => self::getSummary($item['body']),
                    'web_url'   => $item['web_url'],
                );
            }
        }

        if (empty($new_episodes))
            return false;

        Debug::print_r($new_episodes);

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

    public static function cleanUpText($text)
    {
        $text = strip_tags($text);
        return trim($text);
    }

    public static function getSummary($text)
    {
        $text = self::cleanUpText($text);

        // Strip all but the first line.
        $text = strtok($text, "\n");

        // Remove URLs.
        $text = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?).*$)@ui', ' ', $text);

        // Truncate to 300 characters.
        $text = \DF\Utilities::truncateText($text, 300);

        return $text;
    }

    public static function getEntityManager()
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('em');
    }
}