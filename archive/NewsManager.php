<?php
namespace PVL;

use \Entity\News;
use \Entity\Settings;

class NewsManager
{
	public static function run($debug_mode = false)
	{
		$em = \Zend_Registry::get('em');

		$last_run = Settings::getSetting('news_manager_last_run', 0);
		if ($last_run > (time() - 900) && !$debug_mode)
			return;

		$delete_by_type = $em->createQuery('DELETE FROM Entity\News n WHERE n.type = :type');
		$delete_by_type_and_id = $em->createQuery('DELETE FROM Entity\News n WHERE n.type = :type AND n.author_id = :author_id');

		// PVL news first.
		$delete_by_type->setParameter('type', 'pvl')->execute();
		
		$pvl_image_url = \DF\Url::content('pvl_square.png');

		$tumblr_url = 'http://news.ponyvillelive.com/rss';
		$news_items = NewsAdapter\Rss::fetch($tumblr_url);

		if ($news_items)
		{
			foreach($news_items as $item)
			{
				$record = new News;

				$record->fromArray($item);

				$record->is_featured = true;
				$record->type = 'pvl';
				$record->author = 'Ponyville Live!';
				$record->author_id = NULL;
				$record->image_url = $pvl_image_url;
				$em->persist($record);
			}
		}

		$twitter_url = 'http://www.twitter.com/PonyvilleLive';
		$news_items = NewsAdapter\Twitter::fetch($twitter_url, array(
			'include_retweets'		=> FALSE,
			'always_featured'		=> FALSE,
			'use_retweet_count' 	=> FALSE,
			'no_other_social_sites'	=> TRUE,
		));

		if ($news_items)
		{
			foreach($news_items as $item)
			{
				$record = new News;

				$record->fromArray($item);

				$record->type = 'pvl';
				$record->author = 'Ponyville Live!';
				$record->image_url = $pvl_image_url;
				$em->persist($record);
			}
		}

		$em->flush();

		// Station news.
		$delete_by_type->setParameter('type', 'station')->execute();

		$stations = \Entity\Station::fetchArray();

		foreach($stations as $station)
		{
			if (!$station['twitter_url'])
				continue;

			$station_image = \DF\Url::content($station['image_url']);

			$twitter_url = $station['twitter_url'];
			$news_items = NewsAdapter\Twitter::fetch($twitter_url, array(
				'include_retweets'		=> FALSE,
				'always_featured'		=> FALSE,
				'use_retweet_count' 	=> FALSE,
				'no_other_social_sites'	=> FALSE,
				'max_featured_tweets'	=> 3,
			));

			if ($news_items)
			{
				foreach($news_items as $item)
				{
					$record = new News;

					$record->fromArray($item);

					$record->type = 'station';
					$record->author = $station['name'];
					$record->author_id = $station['id'];
					$record->image_url = $station_image;
					$em->persist($record);
				}
			}
		}

		$em->flush();

		Settings::setSetting('news_manager_last_run', time());
	}
}