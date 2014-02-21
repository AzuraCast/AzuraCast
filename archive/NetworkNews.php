<?php
namespace PVL;

class NetworkNews
{
	public static function fetch()
	{
		$network_news = \DF\Cache::get('pvl_homepage_rotator');

    	if (!$network_news)
    	{
    		$timestamp_threshold = strtotime('-6 weeks');

	    	// Pull featured images.
	    	$api_params = array(
	    		'api_key'		=> 'Hp1W4lpJ0dhHA7pOGih0yow02ZXAFHdiIR5bzFS67C0xlERPAZ',
	    		'limit'			=> 10,
	    	);
	    	$api_url = 'http://api.tumblr.com/v2/blog/news.ponyvillelive.com/posts/photo?'.http_build_query($api_params);

	    	$results_raw = @file_get_contents($api_url);
	    	if ($results_raw)
	    	{
	    		$results = json_decode($results_raw, true);
	    		$posts = $results['response']['posts'];

	    		$network_news = array();
	    		foreach((array)$posts as $post)
	    		{
	    			$image = $post['photos'][0]['original_size'];

	    			if ($image['height'] > 250)
	    				continue;

	    			// Copy the image to the local static directory (for SSL and other caching support).
	    			$image_url = $image['url'];
	    			$image_url_basename = basename($image_url);
	    			
	    			$local_path_base = 'rotators/'.$image_url_basename;

	    			$local_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $local_path_base);
	    			$local_url = \DF\Url::content($local_path_base);

	    			if (!file_exists($local_path))
	    				@copy($image_url, $local_path);

	    			$tags = array_map('strtolower', (array)$post['tags']);
	    			if (in_array('archive', $tags))
	    				continue;

	    			$description = \DF\Utilities::truncateText(strip_tags($post['caption']), 250);

	    			if (strpos($description, ':') !== FALSE)
	    			{
	    				list($title, $description) = explode(':', $description, 2);
	    			}
	    			else
	    			{
	    				$title = $description;
	    				$description = NULL;
	    			}

	    			$news_row = array(
	    				'image' => $local_url,
	    				'url' => $post['post_url'],
	    				'title' => trim($title),
	    				'description' => trim($description),
	    				'timestamp' => $post['timestamp'],
	    			);

	    			if ($news_row['timestamp'] >= $timestamp_threshold)
	    				$network_news[] = $news_row;
	    		}
	    	}

	    	usort($network_news, function($a_item, $b_item) {
	    		$a = $a_item['timestamp'];
	    		$b = $b_item['timestamp'];

	    		if ($a == $b)
			        return 0;
			    return ($a < $b) ? 1 : -1;
	    	});

	    	$network_news = array_slice($network_news, 0, 5);

	    	\DF\Cache::set($network_news, 'pvl_homepage_rotator', array(), 120);
	    }

	    return $network_news;
	}
}