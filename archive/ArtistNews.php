<?php
namespace PVL;

use \Entity\Podcast;
use \Entity\News;
use \Entity\Settings;

class ArtistNews
{
	public static function run($debug_mode = false)
	{
		$em = \Zend_Registry::get('em');

		// Clear out old news.
		$clear_old_news = $em->createQuery('DELETE FROM Entity\News n WHERE n.type IN (:type) AND n.source = :source AND n.timestamp <= :threshold')
			->setParameter('type', array('artist', 'podcast'));

		$social_fields = Podcast::getSocialTypes();

		foreach($social_fields as $social_key => $social_info)
		{
			if ($social_info['threshold'])
			{
				$lower_threshold = strtotime($social_info['threshold']);

				$clear_old_news->setParameter('source', $social_key)
					->setParameter('threshold', date('Y-m-d H:i:s', $lower_threshold))
					->execute();
			}
		}

		// Pull podcast news.
		$all_podcasts = $em->createQuery('SELECT p FROM Entity\Podcast p WHERE p.is_approved = 1 ORDER BY p.id ASC')
			->execute();

		foreach($all_podcasts as $podcast)
			self::updateEntity($podcast, 'podcast');

		/*
		// Pull artist news.
		$divisor = 3;

		$last_run = (int)Settings::getSetting('artist_news_modulus', 0);
		$modulus = ($last_run < ($divisor-1)) ? $last_run+1 : 0;

		$all_artists = $em->createQuery('SELECT a FROM Entity\Artist a ORDER BY a.id ASC')
			->getArrayResult();

		$artists_to_sync = array();
		foreach($all_artists as $artist)
		{
			$timestamp = ($artist['sync_timestamp']) ? $artist['sync_timestamp']->getTimestamp() : 0;
			if (($timestamp < (time() - 86400)) || ($artist['id'] % $divisor == $modulus))
			{
				$has_social_info = false;
				foreach($social_fields as $field_key => $field_adapter)
				{
					if (!isset($field_adapter['adapter']))
						continue;

					if (!empty($artist[$field_key]))
						$has_social_info = true;
				}

				if ($has_social_info)
					$artists_to_sync[] = $artist['id'];
			}
		}

		$artists = $em->createQuery('SELECT a FROM Entity\Artist a WHERE a.id IN (:ids) ORDER BY a.id ASC')
			->setParameter('ids', $artists_to_sync)
			->execute();

		foreach($artists as $artist)
			self::updateEntity($artist, 'artist');

		// Update the artist modulus for future sync.
		Settings::setSetting('artist_news_modulus', $modulus);
		*/
	}

	public static function updateEntity($record, $record_type = 'artist')
	{
		$em = \Zend_Registry::get('em');
		$social_fields = Podcast::getSocialTypes();

		$artist_image = \DF\Url::content($record['image_url']);

		$social_types_present = 0;
		foreach($social_fields as $field_key => $field_adapter)
		{
			if (!empty($record[$field_key]) && isset($field_adapter['adapter']))
				$social_types_present++;
		}

		foreach($social_fields as $field_key => $field_adapter)
		{
			if (empty($record[$field_key]) || !isset($field_adapter['adapter']))
				continue;

			$lower_threshold = strtotime($field_adapter['threshold']);

			// Catalog existing news items.
			$existing_news_raw = $em->createQuery('SELECT n FROM Entity\News n WHERE n.type = :type AND n.author_id = :artist_id AND n.source = :source ORDER BY n.id ASC')
				->setParameter('type', $record_type)
				->setParameter('artist_id', $record['id'])
				->setParameter('source', $field_key)
				->getArrayResult();

			$existing_news = array();
			$unfeatured_news = array();
			$featured_news = array();

			foreach((array)$existing_news_raw as $article)
			{
				$existing_news[$article['guid']] = $article;
				$unfeatured_news[$article['id']] = $article['id'];
			}

			// Look for new news items.
			$class_name = '\\PVL\\NewsAdapter\\'.$field_adapter['adapter'];
			$news_items = $class_name::fetch($record[$field_key], $field_adapter['settings']);

			$i = 1;
			foreach((array)$news_items as $item)
			{
				$guid = $item['guid'];
				
				// Featured calculation.
				if (in_array($field_key, array('facebook_url','twitter_url','tumblr_url')))
					$is_featured = (count($social_types_present) < 3 && $i == 1);
				else
					$is_featured = ($i < 3);

				$i++;

				if (!isset($existing_news[$guid]))
				{
					$timestamp = $item['timestamp'];
					if ($timestamp < $lower_threshold)
						continue;

					$article = new News;

					$article->fromArray($item);

					$article->type = $record_type;
					$article->source = $field_key;
					$article->author = $record['name'];
					$article->author_id = $record['id'];
					$article->image_url = $artist_image;
					$article->is_featured = $is_featured;

					$em->persist($article);
				}
				else if ($is_featured)
				{
					$news_id = $existing_news[$guid]['id'];
					$featured_news[$news_id] = $news_id;
					unset($unfeatured_news[$news_id]);
				}
			}
		}

		$update_featured = $em->createQuery('UPDATE Entity\News n SET n.is_featured = :is_featured WHERE n.id IN (:ids)');
		if ($featured_news)
			$update_featured->setParameter('is_featured', 1)->setParameter('ids', $featured_news)->execute();
		if ($unfeatured_news)
			$update_featured->setParameter('is_featured', 0)->setParameter('ids', $unfeatured_news)->execute();

		$record->sync_timestamp = new \DateTime('NOW');
		$em->persist($record);

		$em->flush();
	}
}