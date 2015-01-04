<?php
namespace PVL\NewsAdapter;

class YouTube extends AdapterAbstract
{
    public static function fetch($url, $params = array())
    {
        $config = \Zend_Registry::get('config');

        // Set up Google Client
        $gclient_api_key = $config->apis->google_apis_key;
        $gclient_app_name = $config->application->name;

        if (empty($gclient_api_key))
            return null;

        $gclient = new \Google_Client();
        $gclient->setApplicationName($gclient_app_name);
        $gclient->setDeveloperKey($gclient_api_key);

        $yt_client = new \Google_Service_YouTube($gclient);

        // Retrieve account info from URL processor.
        $account_info = self::getAccount($url);

        // For "User" account types, use "Uploads" playlist.
        if ($account_info['type'] == 'user')
        {
            $data = $yt_client->channels->listChannels('id,contentDetails', array(
                'forUsername' => $account_info['id'],
                'maxResults' => 25,
            ));

            if ($data)
                $playlist_id = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
        }
        else
        {
            $playlist_id = $account_info['id'];
        }

        if (empty($playlist_id))
            return null;

        $data = $yt_client->playlistItems->listPlaylistItems('id,snippet,status,contentDetails', array(
            'playlistId' => $playlist_id,
            'maxResults' => 25,
        ));

        $news_items = array();

        if ($data)
        {
            $feed_items = (array)$data['items'];

            foreach($feed_items as $item)
            {
                $embed_src = 'http://www.youtube.com/watch?v='.$item['contentDetails']['videoId'];

                $news_items[] = array(
                    'guid'          => 'youtube_'.md5($item['id']),
                    'timestamp'     => strtotime($item['snippet']['publishedAt']),
                    'title'         => $item['snippet']['title'],
                    'body'          => $item['snippet']['description'],
                    'web_url'       => $embed_src,
                    'author'        => $item['snippet']['channelTitle'],
                );
            }
        }

        return $news_items;
    }

    public static function getAccount($url)
    {
        if (stristr($url, 'youtube.com') !== FALSE)
        {
            $url_parts = \PVL\Utilities::parseUrl($url);

            if ($url_parts['path_clean'] == 'playlist')
            {
                return array(
                    'type'      => 'playlist',
                    'id'        => $url_parts['query_arr']['list'],
                );
            }
            else
            {
                $url_components = explode('/', $url_parts['path']);
                $url_last_element = array_pop($url_components);
                $account = trim($url_last_element);

                return array(
                    'type'      => 'user',
                    'id'        => $account,
                );
            }
        }
        else
        {
            return array(
                'type'      => 'user',
                'id'        => trim($url),
            );
        }
    }
}