<?php
/**
 * PVL SourceMod Plugin Adapter
 *
 * This script can be hosted on your own server, and modified to support any data service you would like to integrate
 * into SourceMod.
 */

/**
 * @var int Number of seconds to store the local filesystem cache.
 */
$cache_threshold = 15;

/**
 * @var string Remote API URL for song playing data.
 */
$api_url = 'http://api.ponyvillelive.com/nowplaying/index/category/audio';

/**
 * @var string Local cache path, by default uses temp directory.
 */
$cache_path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'pvlive_sourcemod.txt';

/**
 * Do not modify below this section unless you intend to use another service!
 */

// Disable browser caching.
header('Cache-Control: must-revalidate, private, max-age=0');
header('Pragma: no-cache');
header('X-Accel-Expires: off');

// Set content type.
header('Content-Type: text/plain');

// Attempt to pull from local filesystem cache.
if (file_exists($cache_path))
{
    if (filemtime($cache_path) >= (time() - $cache_threshold))
    {
        $fp = fopen($cache_path, 'r');
        fpassthru($fp);
        fclose($fp);
        exit;
    }
}

// Otherwise regenerate local filesystem cache.
@touch($cache_path);
if (!is_writable($cache_path))
    die('Error: Cache path "'.$cache_path.'" not writable!');

$api_result_raw = @file_get_contents($api_url);
$api_result = @json_decode($api_result_raw, true);

if (!isset($api_result['result']))
    die('Error: PVLive API return malformed. Raw return: '.$api_result_raw);

$nowplaying = array();

foreach($api_result['result'] as $np_row)
{
    /**
     * Return format:
     * StationID|StationName|Listeners|SongName|SongArtist
     */
    $station_row = array(
        $np_row['station']['id'],
        $np_row['station']['name'],
        $np_row['listeners']['total'],
        $np_row['current_song']['title'],
        $np_row['current_song']['artist'],
    );

    $nowplaying[] = implode('|', $station_row);
}

$nowplaying_raw = implode('<>', $nowplaying);

// Write nowplaying data to file, then to screen.
@file_put_contents($cache_path, $nowplaying_raw);
echo $nowplaying_raw;