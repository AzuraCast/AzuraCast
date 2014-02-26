<?php
namespace PVL;

use \Entity\Station;
use \Entity\StationMedia;
use \Entity\StationRequest;

class CentovaCast
{
	public static function isStationSupported(Station $station)
	{
		if (!$station->requests_enabled)
			return FALSE;

		$account_username = trim($station->requests_ccast_username);
		if (empty($account_username))
			return FALSE;

		return TRUE;
	}

	public static function getStationID(Station $station)
	{
		if (!self::isStationSupported($station))
			return NULL;

		$db = self::getDatabase();

		$account_username = trim($station->requests_ccast_username);
		$id_raw = $db->fetchAssoc('SELECT id FROM accounts WHERE username = ?', array($account_username));

		if ($id_raw)
			return (int)$id_raw['id'];
		else
			return NULL;
	}

	public static function request(Station $station, $track_id)
	{
		$db = self::getDatabase();
		$em = self::getEntityManager();
		$settings = self::getSettings();

		// Forbid web crawlers from using this feature.
		if (\PVL\Utilities::isCrawler())
			throw new \DF\Exception('Search engine crawlers are not permitted to use this feature.');

		// Verify that the station supports CentovaCast requests.
		$station_id = self::getStationID($station);
		if (!$station_id)
			throw new \DF\Exception('This radio station is not capable of handling requests at this time.');

		// Verify that Track ID exists with station.
		$media_item = StationMedia::getRepository()->findOneBy(array('id' => $track_id, 'station_id' => $station->id));
		if (!($media_item instanceof StationMedia))
			throw new \DF\Exception('The song ID you specified could not be found in the station playlist.');

		// Check the most recent song history.
		try
		{
			$last_play_time = $em->createQuery('SELECT sh.timestamp FROM Entity\SongHistory sh WHERE sh.song_id = :song_id AND sh.station_id = :station_id ORDER BY sh.timestamp DESC')
				->setParameter('song_id', $media_item->song_id)
				->setParameter('station_id', $station->id)
				->setMaxResults(1)
				->getSingleScalarResult();
		}
		catch(\Exception $e)
		{
			$last_play_time = 0;
		}

		if ($last_play_time && $last_play_time > (time() - 60*30))
			throw new \DF\Exception('This song has been played too recently on the station.');

		// Get or create a "requests" playlist for the station.
		$request_playlist_raw = $db->fetchAssoc('SELECT p.id FROM playlists AS p WHERE p.type = ? AND p.accountid = ?', array('request', $station_id));

		if ($request_playlist_raw)
		{
			$playlist_id = $request_playlist_raw['id'];
		}
		else
		{
			$new_playlist = array(
				'title'				=> 'Automated Song Requests',
				'type'				=> 'request',
				'scheduled_repeat' 	=> 'never',
				'scheduled_monthdays' => 'date',
				'interval_type' 	=> 'songs',
				'interval_length'	=> '0',
				'general_weight'	=> '0',
				'status'			=> 'disabled',
				'general_order'		=> 'random',
				'interval_style'	=> 'playall',
				'stateid'			=> '0',
				'accountid'			=> $station_id,
				'scheduled_interruptible' => '0',
				'scheduled_duration' => '0',
				'scheduled_style'	=> 'sequential',
				'general_starttime'	=> '00:00:00',
				'general_endtime'	=> '00:00:00',
				'track_interruptible' => '0',
			);

			$db->insert('playlists', $new_playlist);
			$playlist_id = $db->lastInsertId('playlists');
		}

		// Check for an existing request from this user.
		$user_ip = $_SERVER['REMOTE_ADDR'];

		$existing_request = $db->fetchAll('SELECT ptr.* FROM playlist_tracks_requests AS ptr WHERE ptr.playlistid = ? AND ptr.senderip = ?', array($playlist_id, $user_ip));

		if (count($existing_request) > 0)
			throw new \DF\Exception('You already have a pending request with this station! Please try again later.');

		// Check for any request (on any station) within 5 minutes.
		$recent_threshold = time()-(60*5);

		$recent_requests = $em->createQuery('SELECT sr FROM Entity\StationRequest sr WHERE sr.ip = :user_ip AND sr.timestamp >= :threshold')
			->setParameter('user_ip', $user_ip)
			->setParameter('threshold', $recent_threshold)
			->getArrayResult();

		if (count($recent_requests) > 0)
			throw new \DF\Exception('You have submitted a request too recently! Please wait a while before submitting another one.');

		// Enable the "Automated Song Requests" playlist.
		$db->update('playlists', array('status' => 'enabled'), array('id' => $playlist_id));

		$requesttime = new \DateTime('NOW');
		$requesttime->setTimezone(new \DateTimeZone($settings['timezone']));

		// Create a new request if all other checks pass.
		$new_request = array(
			'playlistid'	=> $playlist_id,
			'trackid'		=> $track_id,
			'requesttime'	=> $requesttime->format('Y-m-d h:i:s'),
			'sendername'	=> 'Ponyville Live!',
			'senderemail'	=> 'requests@ponyvillelive.com',
			'dedication'	=> '',
			'senderip'		=> $user_ip,
		);

		$db->insert('playlist_tracks_requests', $new_request);
		$request_id = $db->lastInsertId('playlist_tracks_requests');

		$media_item->logRequest();
		$media_item->save();

		return $request_id;
	}

	// Routine synchronization of CentovaCast settings
	public static function sync()
	{
		$db = self::getDatabase();
		$em = self::getEntityManager();

		// Force correct account settings (enable global unified request system).
		$account_values = array(
			'allowrequests' 		=> '1',
			'autoqueuerequests' 	=> '1',
			'requestprobability'	=> '50',
			'requestdelay'			=> '0',
			'emailunknownrequests'	=> '0',
		);
		$db->update('accounts', $account_values, array('expectedstate' => 'up'));

		// Clear out old logs.
		$threshold = strtotime('-1 month');
		$threshold_date = date('Y-m-d', $threshold).' 00:00:00';

		$db->executeQuery('DELETE FROM playbackstats_tracks WHERE endtime <= ?', array($threshold_date));
		$db->executeQuery('DELETE FROM visitorstats_sessions WHERE endtime <= ?', array($threshold_date));

		// Delete old requests still listed as pending.
		$requesttime = new \DateTime('NOW');
		$requesttime->modify('-3 hours');
		$requesttime->setTimezone(new \DateTimeZone($settings['timezone']));

		$threshold_requests = $requesttime->format('Y-m-d h:i:s');
		$db->executeQuery('DELETE FROM playlist_tracks_requests WHERE requesttime <= ?', array($threshold_requests));

		// Force playlist enabling for existing pending requests.
		$request_playlists_raw = $db->fetchAll('SELECT DISTINCT ptr.playlistid AS pid FROM playlist_tracks_requests AS ptr');

		foreach($request_playlists_raw as $pl)
		{
			$pl_id = $pl['pid'];
			$db->update('playlists', array('status' => 'enabled'), array('id' => $pl_id));
		}

		// Preload all station media locally.
		$stations = $em->createQuery('SELECT s FROM Entity\Station s WHERE s.requests_enabled = 1')->execute();

		foreach($stations as $station)
		{
			$account_id = self::getStationID($station);
			if (!$account_id)
				continue;

			// Clear existing items.
			$existing_ids_raw = $em->createQuery('SELECT sm FROM Entity\StationMedia sm WHERE sm.station_id = :station_id')
				->setParameter('station_id', $station->id)
				->execute();

			$existing_records = array();
			foreach($existing_ids_raw as $row)
				$existing_records[$row['id']] = $row;

			// Locate all new items.
			$new_records_raw = $db->fetchAll('SELECT t.id, t.title, t.length, tal.name AS album_name, tar.name AS artist_name 
				FROM tracks AS t 
				INNER JOIN track_albums AS tal ON t.albumid = tal.id 
				INNER JOIN track_artists AS tar ON t.artistid = tar.id 
				WHERE t.accountid = ?', array($account_id));

			$new_records = array();
			foreach($new_records_raw as $track_info)
			{
				if ($track_info['length'] < 60)
					continue;

				$row = array(
					'id'		=> $track_info['id'],
					'title'		=> $track_info['title'],
					'artist'	=> $track_info['artist_name'],
					'album'		=> $track_info['album_name'],
					'length'	=> $track_info['length'],
				);
				$new_records[$row['id']] = $row;
			}

			// Reconcile differences.
            $existing_guids = array_keys($existing_records);
            $new_guids = array_keys($new_records);

            $guids_to_delete = array_diff($existing_guids, $new_guids);
            if ($guids_to_delete)
            {
                foreach($guids_to_delete as $guid)
                {
                    $record = $existing_records[$guid];
                    $em->remove($record);
                }
            }

            $guids_to_add = array_diff($new_guids, $existing_guids);
            if ($guids_to_add)
            {
                foreach($guids_to_add as $guid)
                {
                	$record = new StationMedia;
                	$record->station = $station;
                	$record->fromArray($new_records[$guid]);
                	$em->persist($record);
                }
            }

            $em->flush();
		}
	}

	public static function getEntityManager()
	{
		return \Zend_Registry::get('em');
	}

	public static function getDatabase()
	{
		static $db;
		if (!$db)
		{
			$settings = self::getSettings();

			$config = new \Doctrine\DBAL\Configuration;
			$connectionParams = array(
				'host'		=> $settings['host'],
				'dbname'	=> $settings['db_name'],
				'user'		=> $settings['db_user'],
				'password'	=> $settings['db_pass'],
				'driver'	=> 'pdo_mysql',
			);
			$db = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
		}

		return $db;
	}

	public static function getSettings()
	{
		static $settings;
		if (!$settings)
		{
			$config = \Zend_Registry::get('config');
			$settings = $config->services->centova->toArray();
		}

		return $settings;
	}

}