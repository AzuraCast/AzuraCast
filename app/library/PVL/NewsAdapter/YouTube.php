<?php
namespace PVL\NewsAdapter;

class YouTube extends AdapterAbstract
{
    public static function fetch($url, $params = array())
    {
        $config = \Zend_Registry::get('config');
        $v3_api_key = $config->apis->youtube_v3;

        $client = new \Zend_Http_Client();
        $client->setConfig(array(
            'timeout'       => 20,
            'keepalive'     => true,
        ));

        $account_info = self::getAccount($url);

        if ($account_info['type'] == 'user')
        {
            $client->setUri('https://www.googleapis.com/youtube/v3/channels');
            $client->setParameterGet(array(
                'part'      => 'id,contentDetails',
                'forUsername' => $account_info['id'],
                'maxResults' => 25,
                'key'       => $v3_api_key,
            ));

            $response = $client->request('GET');

            if ($response->isSuccessful())
            {
                $response_text = $response->getBody();
                $data = @json_decode($response_text, TRUE);

                $playlist_id = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
            }
        }
        else
        {
            $playlist_id = $account_info['id'];
        }

        if (!$playlist_id)
            return null;

        $client->setUri('https://www.googleapis.com/youtube/v3/playlistItems');
        $client->setParameterGet(array(
            'part'      => 'id,snippet,status,contentDetails',
            'playlistId' => $playlist_id,
            'maxResults' => 25,
            'key'       => $v3_api_key,
        ));

        $response = $client->request('GET');
        $news_items = array();

        if ($response->isSuccessful())
        {
            $response_text = $response->getBody();
            $data = @json_decode($response_text, TRUE);

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