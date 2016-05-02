<?php
namespace App\NewsAdapter;

class LiveStream extends AdapterAbstract
{
    public static function fetch($url, $params = array())
    {
        $client = new \GuzzleHttp\Client([
            'timeout'       => 20,
            'keepalive'     => true,
        ]);

        $username = self::getAccount($url);

        $uri = 'http://x'.$username.'x.api.channel.livestream.com/2.0/latestclips.json';
        $response = $client->get($uri, [
            'query' => [
                'page'      => 1,
                'maxresults' => 25,
            ],
        ]);

        $news_items = array();

        if ($response->getStatusCode() <= 300)
        {
            $response_text = $response->getBody()->getContents();
            $data = @json_decode($response_text, TRUE);

            $feed_items = (array)$data['channel']['item'];

            foreach($feed_items as $item)
            {
                $news_items[] = array(
                    'guid'          => 'livestream_'.md5($item['guid']),
                    'timestamp'     => strtotime($item['pubDate']),
                    'title'         => $item['title'],
                    'body'          => $item['description'],
                    'web_url'       => $item['link'],
                    'author'        => $data['channel']['title'],
                );
            }
        }

        return $news_items;
    }

    public static function getAccount($url)
    {
        $url_parts = \PVL\Utilities::parseUrl($url);

        $url_components = explode('/', $url_parts['path']);
        $url_last_element = array_pop($url_components);
        $account = trim($url_last_element);
        return $account;
    }
}