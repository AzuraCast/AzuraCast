<?php
namespace PVL;

use \Entity\Video;
use \Entity\Schedule;
use \Entity\Settings;

use PVL\Debug;
use PVL\Service\PvlNode;

class VideoManager
{
    public static function generate()
    {
        set_time_limit(60);

        // Fix DF\URL // prefixing.
        \DF\Url::forceSchemePrefix(true);

        $nowplaying = self::loadNowPlaying();

        // Generate PVL API cache.
        \DF\Cache::remove('api_nowplaying_video');
        \DF\Cache::save($nowplaying, 'api_nowplaying_video', 60);

        // Generate PVL API nowplaying file.
        $nowplaying_api = json_encode(array('status' => 'success', 'result' => $nowplaying), JSON_UNESCAPED_SLASHES);
        $file_path_api = DF_INCLUDE_STATIC.'/api/nowplaying_video.json';

        @file_put_contents($file_path_api, $nowplaying_api);

        // Push to live-update service.
        PvlNode::push('nowplaying_video', $nowplaying);
    }

    public static function loadNowPlaying()
    {
        Debug::startTimer('Video Nowplaying Overall');

        $em = self::getEntityManager();
        $stations = Video::fetchAll();

        $nowplaying = array();

        foreach($stations as $station)
        {
            Debug::startTimer($station->name);

            $name = $station->short_name;
            $nowplaying[$name] = self::processStation($station);

            Debug::endTimer($station->name);
        }

        Debug::endTimer('Nowplaying Overall');

        return $nowplaying;
    }

    /**
     * Generate Structured NowPlaying Data
     *
     * @param Video $station
     * @return array Structured NowPlaying Data
     */
    public static function processStation(Video $station)
    {
        $em = self::getEntityManager();

        $np_old = (array)$station->nowplaying_data;

        $np = array();
        $np['status'] = 'offline';
        $np['station'] = Video::api($station);

        // Process stream.
        $custom_class = Video::getStationClassName($station->name);
        $custom_adapter = '\\PVL\\VideoAdapter\\'.$custom_class;

        $stream_np = array();
        if (class_exists($custom_adapter))
        {
            $np_adapter = new $custom_adapter($station);
            $stream_np = $np_adapter->process();
        }
        else
        {
            $adapters = array(
                new \PVL\VideoAdapter\Livestream($station),
                new \PVL\VideoAdapter\TwitchTv($station),
            );

            foreach($adapters as $np_adapter)
            {
                if ($np_adapter->canHandle())
                {
                    $stream_np = $np_adapter->process();
                    break;
                }
            }
        }

        Debug::print_r($stream_np);

        $np = array_merge($np, $stream_np);

        Debug::log('Adapter Class: '.get_class($np_adapter));

        $np['status'] = $np['meta']['status'];
        $np['listeners'] = $np['meta']['listeners'];

        $station->nowplaying_data = $np;

        $em->persist($station);
        $em->flush();

        return $np;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     * @throws \Zend_Exception
     */
    public static function getEntityManager()
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('em');
    }
}