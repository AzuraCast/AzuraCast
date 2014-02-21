<?php
/**
 * Miscellaneous Utilities Class
 **/

namespace DF;

class Utilities
{
	public static function getNewsFeed($feed_url, $cache_name = NULL, $cache_expires = 900)
	{
		if (!is_null($cache_name))
		{
			$feed_cache = \DF\Cache::get('feed_'.$cache_name);
		}
		
		if (!$feed_cache)
		{
			// Catch the occasional error when the RSS feed is malformed or the HTTP request times out.
			try
			{
				$http_client = \Zend_Feed::getHttpClient();
				$http_client->setConfig(array('timeout' => 60));
				
				$news_feed = new \Zend_Feed_Rss($feed_url);
			}
			catch(Exception $e)
			{
				$news_feed = NULL;
			}
			
			if (!is_null($news_feed))
			{	
				$latest_news = array();
				$article_num = 0;
				
				foreach ($news_feed as $item)
				{
					$article_num++;
					
					// Process categories.
					$categories_raw = (is_array($item->category)) ? $item->category : array($item->category);
					$categories = array();
					
					foreach($categories_raw as $category)
					{
						$categories[] = $category->__toString();
					}
				
					// Process main description.
					$description = trim($item->description()); // Remove extraneous tags.
					// $description = preg_replace('/[^(\x20-\x7F)]+/',' ', $description); // Strip "exotic" non-ASCII characters.
					// $description = preg_replace('/<a[^(>)]+>read more<\/a>/i', '', $description); // Remove "read more" link.
					
					$news_item = array(
						'num'			=> $article_num,
						'title'			=> $item->title(),
						'timestamp'		=> strtotime($item->pubDate()),
						'description'	=> $description,
						'link'			=> $item->link(),
						'categories'	=> $categories,
					);
					
					$latest_news[] = $news_item;
				}

				$latest_news = array_slice($latest_news, 0, 10);
				
				if (!is_null($cache_name))
				{
					\DF\Cache::set($latest_news, 'feed_'.$cache_name, array('feeds', $cache_name));
				}
			}
		}
		else
		{
			$latest_news = $feed_cache;
		}
		
		return $latest_news;
	}
	
	/**
	 * Random Image
	 */
	
	public static function randomImage($static_dir)
	{
	    $img = null;
	    
	    $folder = DF_INCLUDE_STATIC.DIRECTORY_SEPARATOR.$static_dir;
	    $extList = array(
	      'gif'		=> 'image/gif',
	      'jpg'		=> 'image/jpeg',
	      'jpeg'		=> 'image/jpeg',
	      'png'		=> 'image/png',
	    );
		
	    $handle = opendir($folder);
	    while ($file = readdir($handle))
	    {
	      $file_info = pathinfo($file);
	      $file_ext = strtolower($file_info['extension']);
	      if (isset($extList[$file_ext]))
	        $fileList[] = $file;
	    }
	    closedir($handle);
	    
	    if (count($fileList) > 0)
	    {
	      $imageNumber = time() % count($fileList);
	      $img = $fileList[$imageNumber];
	    }
	    
	    return \DF\Url::content($static_dir.'/'.$img);
	}

	/**
	 * Password Generation
	 */

	const PASSWORD_LENGTH = 9;
	
	// Replacement for print_r.
	public static function print_r($var, $return = FALSE)
	{
		$return_value = '<pre style="font-size: 13px; font-family: Consolas, Courier New, Courier, monospace; color: #000; background: #EFEFEF; border: 1px solid #CCC; padding: 5px;">';
		$return_value .= print_r($var, TRUE);
		$return_value .= '</pre>';
		
		if ($return)
		{
			return $return_value;
		}
		else
		{
			echo $return_value;
		}
	}
    
    /**
     * Number handling
     */
    
    public static function ceiling($value, $precision = 0) {
        return ceil($value * pow(10, $precision)) / pow(10, $precision);
    }
    public static function floor($value, $precision = 0) {
        return floor($value * pow(10, $precision)) / pow(10, $precision);
    }
    
    public static function money_format($number)
    {
		if ($number < 0)
			return '-$'.number_format(abs($number), 2);
		else
			return '$'.number_format($number, 2);
	}
	
	public static function getFiscalYear($timestamp = NULL)
	{
		if ($timestamp === NULL)
			$timestamp = time();
		
		$fiscal_year = intval(date('Y', $timestamp));
		$fiscal_month = intval(date('m', $timestamp));
		
		if ($fiscal_month >= 9)
			$fiscal_year++;
		return $fiscal_year;
	}
	
	/**
	 * Security
	 */
	public static function generatePassword($char_length = self::PASSWORD_LENGTH)
	{
		// String of all possible characters. Avoids using certain letters and numbers that closely resemble others.
		$numeric_chars = str_split('234679');
		$uppercase_chars = str_split('ACDEFGHJKLMNPQRTWXYZ');
		$lowercase_chars = str_split('acdefghjkmnpqrtwxyz');
		
		$chars = array($numeric_chars, $uppercase_chars, $lowercase_chars);
		
		$password = '';
		for($i = 1; $i <= $char_length; $i++)
		{
			$char_array = $chars[$i % 3];
			$password .= $char_array[mt_rand(0, count($char_array)-1)];
		}
		
		return str_shuffle($password);
	}
	
	// Get the plain-english value of a given timestamp.
	public static function timeToText($timestamp)
	{
		return self::timeDifferenceText(0, $timestamp);
	}
	
	// Get the plain-english difference between two timestamps.
	public static function timeDifferenceText($timestamp1, $timestamp2)
	{
		$time_diff = abs($timestamp1 - $timestamp2);
		$diff_text = "";
		
		if ($time_diff < 60)
		{
			$time_num = intval($time_diff);
			$time_unit = 'second';
		}
		else if ($time_diff >= 60 && $time_diff < 3600)
		{
			$time_num = round($time_diff / 60, 1);
			$time_unit = 'minute';
		}
		else if ($time_diff >= 3600 && $time_diff < 216000)
		{
			$time_num = round($time_diff / 3600, 1);
			$time_unit = 'hour';
		}
		else if ($time_diff >= 216000 && $time_diff < 10368000)
		{
			$time_num = round($time_diff / 86400);
			$time_unit = 'day';
		}
		else
		{
			$time_num = round($time_diff / 2592000);
			$time_unit = 'month';
		}
		
		$diff_text = $time_num.' '.$time_unit.(($time_num != 1)?'s':'');
		
		return $diff_text;
	}

	/**
     * Truncate text (adding "..." if needed)
     */
    public static function truncateText($text, $limit = 80, $pad = '...')
    {
        if (strlen($text) <= $limit)
        {
            return $text;
        }
        else
        {
            $wrapped_text = wordwrap($text, $limit, "{N}", TRUE);
            $shortened_text = substr($wrapped_text, 0, strpos($wrapped_text, "{N}"));
            
            // Prevent the padding string from bumping up against punctuation.
            $punctuation = array('.',',',';','?','!');
            if (in_array(substr($shortened_text, -1), $punctuation))
            {
                $shortened_text = substr($shortened_text, 0, -1);
            }
            
            return $shortened_text.$pad;
        }
    }

    public static function truncateUrl($url, $length=40)
    {
    	$url = str_replace(array('http://', 'https://', 'www.'), array('', '', ''), $url);
		return self::truncateText(rtrim($url, '/'), $length);
    }
    
    /**
     * Array Combiner (useful for configuration files)
     */
    public static function pairs($array)
    {
		return array_combine($array, $array);
    }

    public static function columns($array, $num_cols = 2, $preserve_keys = true)
    {
    	$items_total = (int)count($array);
    	$items_per_col = ceil($items_total / $num_cols);
    	return array_chunk($array, $items_per_col, $preserve_keys);
    }
}
