<?php
namespace PVL\NewsAdapter;

class Twitter extends AdapterAbstract
{
    public static function getAccount($url)
    {
        if (stristr($url, 'twitter.com') !== FALSE)
            return trim(array_pop(explode('/', rtrim($url, '/'))));
        else
            return trim($url);
    }

    public static function fetch($url, $params = array())
    {
        $news_items = array();

        $twitter_username = self::getAccount($url);

        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');

        $twitter_conf = $config->apis->twitter->toArray();

        $twitter = new \tmhOAuth($twitter_conf);

        $twitter->request('GET', 'https://api.twitter.com/1.1/statuses/user_timeline.json', array(
            'screen_name' => $twitter_username,
            'count' => 100,
            'exclude_replies' => true,
        ));

        $response_raw = $twitter->response['response'];
        $response = json_decode($response_raw, true);

        if (!$response)
            return array();

        // Determine "top percentile" for retweets.
        $retweet_min = 0;
        if ($params['use_retweet_count'])
        {
            $highest_retweet = 0;
            foreach($response as $tweet)
            {
                if (!$params['include_retweets'] && $tweet['retweeted_status'])
                    continue;

                if ($tweet['retweet_count'] > $highest_retweet)
                    $highest_retweet = $tweet['retweet_count'];
            }

            if ($highest_retweet < 5)
                $retweet_min = 1;
            else
                $retweet_min = floor(0.7*$highest_retweet);
        }

        $featured_tweets = 0;

        // Filter through to find "featured" tweets.
        foreach($response as $tweet)
        {
            if (!$params['include_retweets'] && isset($tweet['retweeted_status']))
                continue;

            $text_raw = $tweet['text'];

            // Fix "smart quote" issues.
            $text_raw = self::filterSmartQuotes($text_raw);
            
            $text_replacements = array();

            // Add in links to referenced URLs.
            if ($tweet['entities']['urls'])
            {
                foreach($tweet['entities']['urls'] as $url)
                {
                    $start = $url['indices'][0];
                    $end = $url['indices'][1];

                    $orig = $url['url'];
                    $text_replacements[$orig] = '<a href="'.$url['expanded_url'].'" target="_blank">'.$orig.'</a>';
                }
            }

            if ($tweet['entities']['user_mentions'])
            {
                foreach($tweet['entities']['user_mentions'] as $mention)
                {
                    $start = $mention['indices'][0];
                    $end = $mention['indices'][1];

                    $real_start = strpos($text_raw, '@', $start);
                    $real_len = ($end - $start);
                    $orig = substr($text_raw, $real_start, $real_len);

                    $text_replacements[$orig] = '<a href="http://www.twitter.com/'.$mention['screen_name'].'" target="_blank">'.$orig.'</a>';
                }
            }

            $text = str_replace(array_keys($text_replacements), array_values($text_replacements), $text_raw);

            if ($params['always_featured'])
            {
                $is_featured = true;
            }
            else
            {
                $is_featured = true;

                if ((int)$tweet['retweet_count'] < $retweet_min && $params['use_retweet_count'])
                    $is_featured = false;

                $social_sources = array('tumblr', 'facebook');
                if ($params['no_other_social_sites'])
                {
                    foreach($social_sources as $social_source)
                    {
                        if (stristr($tweet['source'], $social_source) !== false)
                            $is_featured = false;
                    }
                }

                if (strlen($text_raw) < 30)
                    $is_featured = false;
            }

            if ($featured_tweets >= $params['max_featured_tweets'])
                $is_featured = false;

            if ($is_featured)
                $featured_tweets++;

            $news_item = array(
                'guid'      => 'twitter_'.$tweet['id_str'],
                'timestamp' => strtotime($tweet['created_at']),
                'title'     => $text,
                'body' => '<a href="http://www.twitter.com/'.$twitter_username.'" target="_blank">Follow @'.$twitter_username.' on Twitter</a>',
                'web_url'   => 'http://www.twitter.com/'.$twitter_username.'/status/'.$tweet['id_str'],
                'is_featured' => $is_featured,
            );
            $news_items[] = $news_item;
        }

        return $news_items;
    }
}