<?php
namespace PVL\NewsAdapter;

class YouTube extends AdapterAbstract
{
	public static function getAccount($url)
	{
		if (stristr($url, 'youtube.com') !== FALSE)
		{
			$url_parts = parse_url($url);
			$path = rtrim($url_parts['path'], '/');

			$url_components = explode('/', $path);
			$url_last_element = array_pop($url_components);
			$account = trim($url_last_element);
		}
		else
		{
			$account = trim($url);
		}

		return $account;
	}

	public static function fetch($url, $params = array())
	{
		$api_key = 'AI39si5oveOXVqbMmAeF-7oZ8NeDTeqo27ghNJVTi1yTBVpf3j03Vfs2hx908oI0zCAF07tpFS4N8cAXJnH9TaYhFPKWN6_hLA';

		$username = self::getAccount($url);

		$client = new \Zend_Http_Client();
		$client->setConfig(array(
			'timeout'		=> 20,
			'keepalive'		=> true,
		));

		$client->setUri('http://gdata.youtube.com/feeds/api/videos');
		$client->setParameterGet(array(
			'alt' 		=> 'json',
			'author' 	=> $username,
			'orderby' 	=> 'published',
			'safeSearch' => 'none',
			'racy'		=> 'include',
		));

		$client->setHeaders('GData-Version: 2');
		$client->setHeaders('X-GData-Key: key='.$api_key);
		$response = $client->request('GET');

		$news_items = array();

		if ($response->isSuccessful())
		{
			$response_text = $response->getBody();
			$data = @json_decode($response_text, TRUE);

			$feed_items = (array)$data['feed']['entry'];

			foreach($feed_items as $item)
			{
				$embed_src = $item['link'][0]['href'];

				$thumbnails = (array)$item['media$group']['media$thumbnail'];
				foreach($thumbnails as $thumb)
				{
					if (stristr($thumb['url'], 'mqdefault') !== FALSE)
						$thumbnail = $thumb['url'];
				}
				if (!$thumbnail)
					$thumbnail = $thumbnails[0]['url'];

				$body = '<a class="fancybox fancybox.iframe" href="'.$embed_src.'"><img src="'.$thumbnail.'" alt="Click to Watch Video"></a>';
				$body .= '<p>'.$item['media$group']['media$description']['$t'].'</p>';

				$news_items[] = array(
					'guid' 			=> 'youtube_'.md5($item['id']['$t']),
					'timestamp'		=> strtotime($item['published']['$t']),
					'title'			=> $item['title']['$t'],
					'body'			=> $body,
					'web_url'		=> $embed_src,
					'author'		=> $item['author'][0]['name']['$t'],
				);
			}
		}

		return $news_items;
	}
}