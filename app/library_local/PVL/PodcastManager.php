<?php
namespace PVL;

use \Entity\Podcast;
use \Entity\PodcastEpisode;
use \Entity\Settings;

class PodcastManager
{
    public static function run($debug_mode = false)
    {
        $em = \Zend_Registry::get('em');
        $social_fields = Podcast::getSocialTypes();

        // Pull podcast news.
        $all_podcasts = $em->createQuery('SELECT p, pe FROM Entity\Podcast p LEFT JOIN p.episodes pe WHERE p.is_approved = 1 ORDER BY p.id ASC')
            ->execute();

        foreach($all_podcasts as $record)
        {
            // Get current GUID records.
            $existing_episodes = array();

            foreach($record->episodes as $episode)
                $existing_episodes[$episode->guid] = $episode;

            // Pull new GUID records.
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
            $existing_guids = array_keys($existing_episodes);
            $new_guids = array_keys($new_episodes);

            $guids_to_delete = array_diff($existing_guids, $new_guids);
            if ($guids_to_delete)
            {
                foreach($guids_to_delete as $guid)
                {
                    $episode = $existing_episodes[$guid];
                    $episode->delete();
                }
            }

            $guids_to_add = array_diff($new_guids, $existing_guids);

            if ($guids_to_add)
            {
                foreach($guids_to_add as $guid)
                {
                    $episode = new PodcastEpisode;
                    $episode->podcast = $record;
                    $episode->fromArray($new_episodes[$guid]);
                    $episode->save();
                }
            }
        }

        return true;
    }

    public static function cleanUpText($text)
    {
        $text = strip_tags($text);
        return trim($text);
    }
}