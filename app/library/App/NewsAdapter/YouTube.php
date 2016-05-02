<?php
namespace App\NewsAdapter;

class YouTube extends AdapterAbstract
{
    public static function fetch($url, $params = array())
    {
        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');

        // Set up Google Client
        $gclient_api_key = $config->apis->google_apis_key;
        $gclient_app_name = $config->application->name;

        if (empty($gclient_api_key))
            return null;

        $gclient = new \Google_Client();
        $gclient->setApplicationName($gclient_app_name);
        $gclient->setDeveloperKey($gclient_api_key);

        $yt_client = new \App\Service\YouTube($gclient);

        // Retrieve account info from URL processor.
        $account_info = self::getAccount($url);

        // For "User" account types, use "Uploads" playlist.
        if ($account_info['type'] == 'user')
        {
            $data = $yt_client->channels->listChannels('id,contentDetails', array(
                'forUsername' => $account_info['id'],
                'maxResults' => 1,
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

        $data = $yt_client->getPlaylistItems($playlist_id);
        $news_items = array();

        foreach((array)$data as $item)
        {
            if ($item['status']['privacyStatus'] !== 'public')
                continue;

            if ($item['status']['uploadStatus'] !== 'processed')
                continue;

            $embed_src = 'http://www.youtube.com/watch?v='.$item['id'];

            $news_items[] = array(
                'guid'          => 'youtube_'.md5($item['id']),
                'timestamp'     => strtotime($item['snippet']['publishedAt']),
                'title'         => $item['snippet']['title'],
                'body'          => $item['snippet']['description'],
                'web_url'       => $embed_src,
                'thumbnail_url' => \App\Service\YouTube::getThumbnail($item['snippet']['thumbnails'], 'medium'),
                'banner_url'    => \App\Service\YouTube::getThumbnail($item['snippet']['thumbnails'], 'large'),
                'author'        => $item['snippet']['channelTitle'],
            );
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