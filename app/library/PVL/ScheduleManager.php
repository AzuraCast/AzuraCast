<?php
namespace PVL;

use \Entity\Station;
use \Entity\Schedule;
use \Entity\Settings;

class ScheduleManager
{
    public static function run($force_run = false)
    {
        $em = \Zend_Registry::get('em');
        $config = \Zend_Registry::get('config');

        // Set up Google Client.
        $gclient_api_key = $config->apis->google_apis_key;
        $gclient_app_name = $config->application->name;

        if (empty($gclient_api_key))
            return null;

        $gclient = new \Google_Client();
        $gclient->setApplicationName($gclient_app_name);
        $gclient->setDeveloperKey($gclient_api_key);

        $gcal = new \Google_Service_Calendar($gclient);

        // Prevent running repeatedly in too short of a time (avoid API limits).
        $last_run = Settings::getSetting('schedule_manager_last_run', 0);
        if ($last_run > (time() - 300) && !$force_run)
            return null;

        $schedule_items = array();
        $schedule_records = array();

        $stations = $em->createQuery('SELECT s FROM Entity\Station s WHERE s.category IN (:types) AND s.is_active = 1')
            ->setParameter('types', array('audio', 'video'))
            ->getArrayResult();

        foreach($stations as $station)
        {
            if ($station['gcal_url'])
            {
                $schedule_items[] = array(
                    'name'      => $station['name'],
                    'url'       => $station['gcal_url'],
                    'type'      => 'station',
                    'station_id' => $station['id'],
                    'image_url' => \DF\Url::content($station['image_url']),
                );
            }
        }

        Debug::startTimer('Get Calendar Records');

        // Time boundaries for calendar entries.
        $threshold_start = date(\DateTime::RFC3339, strtotime('-1 week'));
        $threshold_end = date(\DateTime::RFC3339, strtotime('+1 year'));

        foreach($schedule_items as $item)
        {
            // Get the "calendar_id" from the URL provided by the user.
            $orig_url_parts = parse_url($item['url']);
            $url_path_parts = explode('/', $orig_url_parts['path']);

            $calendar_id = urldecode($url_path_parts[3]);

            if (empty($calendar_id))
                continue;

            // Call the external Google Calendar client.
            try
            {
                $all_events = $gcal->events->listEvents($calendar_id, array(
                    'timeMin'       => $threshold_start,
                    'timeMax'       => $threshold_end,
                    'singleEvents'  => 'true',
                    'orderBy'       => 'startTime',
                    'maxResults'    => '300',
                ));
            }
            catch(\Exception $e) { continue; }

            // Process each individual event.
            foreach($all_events as $event_orig)
            {
                $title = $event_orig->summary;
                $body = $event_orig->description;
                $location = $event_orig->location;
                $web_url = $event_orig->htmlLink;

                $is_all_day = false;

                $start_time_obj = $event_orig->start;
                if ($start_time_obj->date)
                {
                    $is_all_day = true;
                    $start_time = strtotime($start_time_obj->date.' 00:00:00');
                }
                else
                {
                    $start_time = strtotime($start_time_obj->dateTime);
                }

                $end_time_obj = $event_orig->end;
                if ($end_time_obj->date)
                {
                    $is_all_day = true;
                    $end_time = strtotime($end_time_obj->date.' 00:00:00');
                }
                elseif ($end_time_obj)
                {
                    $end_time = strtotime($end_time_obj->dateTime);
                }
                else
                {
                    $end_time = $start_time;
                }

                // Detect URLs.
                if ($body)
                {
                    preg_match('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@',$body, $urls);

                    if (count($urls) > 0)
                        $web_url = $urls[0];
                }

                $guid = md5(implode('|', array($event_orig->id, $start_time, $end_time, $title, $location)));

                $schedule_record = array(
                    'guid'      => $guid,
                    'type'      => $item['type'],
                    'start_time' => $start_time,
                    'end_time'  => $end_time,
                    'is_all_day' => $is_all_day,
                    'title'     => $title,
                    'location'  => $location,
                    'body'      => $body,
                    'image_url' => $item['image_url'],
                    'web_url'   => $web_url,
                );

                \PVL\Debug::print_r($schedule_record);

                $schedule_records[$item['station_id']][$guid] = $schedule_record;
            }
        }

        Debug::endTimer('Get Calendar Records');
        
        if (count($schedule_records) == 0)
        {
            Debug::log('Error: No calendar records loaded');
            return;
        }

        // Add/Remove all differential records.
        Debug::startTimer('Sync DB Records');

        $em->createQuery('DELETE FROM Entity\Schedule s WHERE s.station_id NOT IN (:station_ids)')
            ->setParameter('station_ids', array_keys($schedule_records))
            ->execute();

        foreach($schedule_records as $station_id => $station_records)
        {
            $station = Station::find($station_id);

            if ($station_id == 0)
            {
                $existing_guids_raw = $em->createQuery('SELECT s.guid FROM Entity\Schedule s WHERE s.station_id IS NULL')
                    ->getArrayResult();
            }
            else
            {
                $existing_guids_raw = $em->createQuery('SELECT s.guid FROM Entity\Schedule s WHERE s.station_id = :sid')
                    ->setParameter('sid', $station_id)
                    ->getArrayResult();
            }

            $existing_guids = array();
            foreach($existing_guids_raw as $i)
                $existing_guids[] = $i['guid'];

            $new_guids = array_keys($station_records);

            $guids_to_delete = array_diff($existing_guids, $new_guids);

            if ($guids_to_delete)
            {
                $em->createQuery('DELETE FROM Entity\Schedule s WHERE s.guid IN (:guids)')
                    ->setParameter('guids', $guids_to_delete)
                    ->execute();
            }

            $guids_to_add = array_diff($new_guids, $existing_guids);

            if ($guids_to_add)
            {
                foreach($guids_to_add as $guid)
                {
                    $schedule_record = $station_records[$guid];

                    $record = new Schedule;
                    $record->station = $station;
                    $record->fromArray($schedule_record);
                    $em->persist($record);
                }
            }

            $em->flush();
            $em->clear();
        }

        Debug::endTimer('Sync DB Records');

        Settings::setSetting('schedule_manager_last_run', time());
    }

    public static function requestExternalUrl($url, $name = 'Calendar')
    {
        Debug::startTimer('Request URL '.$name);
        Debug::log($url);

        // Start cURL request.
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 10); 
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2');  

        $return_raw = \PVL\Utilities::curl_exec_utf8($curl);
        // End cURL request.

        Debug::endTimer('Request URL '.$name);

        $error = curl_error($curl);
        if ($error)
            Debug::log('Curl Error:'.$error);
        
        return $return_raw;
    }
    
    public static function formatName($name)
    {
        list($title, $city) = explode('-', $name);

        $special_codes = array(
            '@' => array('icon' => 'icon-calendar', 'text' => 'PVL Special Event'),
            '*' => array('icon' => 'icon-star', 'text' => 'Full PVL Coverage'),
            '+' => array('icon' => 'icon-star-half-full', 'text' => 'Partial PVL Coverage'),
            '!' => array('icon' => 'icon-group', 'text' => 'No PVL Coverage'),
        );

        $return = array(
            'title' => trim($title),
            'city' => trim($city),
            'title_full' => trim($title),
            'category' => '',
            'icon' => '',
        );

        foreach($special_codes as $symbol => $full)
        {
            if (substr($return['title'], 0, strlen($symbol)) == $symbol)
            {
                $return['category'] = $full['text'];
                $return['icon'] = $full['icon'];

                $title_addon = '<br><small><i class="'.$full['icon'].'"></i> '.$full['text'].'</small>';

                $return['title'] = trim(substr($return['title'], strlen($symbol)));
                $return['title_full'] = $return['title'].$title_addon;
                break;
            }
        }

        return $return;
    }

}