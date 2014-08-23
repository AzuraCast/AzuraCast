<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;
use \Entity\StationStream;

class AdapterAbstract
{
    protected $stream;
    protected $station;

    protected $url;

    /**
     * @param StationStream $stream
     * @param Station $station
     */
    public function __construct(StationStream $stream, Station $station)
    {
        $this->stream = $stream;
        $this->station = $station;

        $this->url = $stream->nowplaying_url;
    }

    /* Master processing and cleanup. */
    public function process()
    {
        // Now Playing defaults.
        $np = array(
            'current_song' => array(
                'text'          => 'Stream Offline',
                'title'         => '',
                'artist'        => '',
            ),
            'listeners' => array(
                'current'       => 0,
                'unique'        => null,
                'total'         => null,
            ),
            'meta' => array(
                'status'        => 'offline',
                'bitrate'       => 0,
                'format'        => '',
            ),
        );

        // Merge station-specific info into defaults.
        $this->_process($np);

        // Update status code for offline stations, clean up song info for online ones.
        if ($np['current_song']['text'] == 'Stream Offline')
            $np['meta']['status'] = 'offline';
        else
            array_walk($np['current_song'], array($this, '_cleanUpString'));

        // Fill in any missing listener info.
        if ($np['listeners']['unique'] === null)
            $np['listeners']['unique'] = $np['listeners']['current'];

        if ($np['listeners']['total'] === null)
            $np['listeners']['total'] = $np['listeners']['current'];

        \PVL\Debug::log($np);

        return $np;
    }

    protected function _cleanUpString(&$value)
    {
        $value = htmlspecialchars_decode($value);
        $value = trim($value);
    }

    /* Stub function for the process internal handler. */
    protected function _process(&$np)
    {
        return false;
    }

    /* Fetch a remote URL. */
    protected function getUrl($c_opts = null, $cache_time = 0)
    {
        // Compose cURL configuration array.
        if (is_null($c_opts))
            $c_opts = array();
        elseif (!is_array($c_opts))
            $c_opts = array('url' => $c_opts);

        $c_defaults = array(
            'url'       => $this->url,
            'method'    => 'GET',
            'useragent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2',
        );
        $c_opts = array_merge($c_defaults, $c_opts);

        $cache_name = 'nowplaying_url_'.substr(md5($c_opts['url']), 0, 10);
        if ($cache_time > 0)
        {
            $return_raw = \DF\Cache::load($cache_name);
            if ($return_raw)
                return $return_raw;
        }

        \PVL\Debug::startTimer('Make cURL Request');

        $postfields = false;
        if (!empty($c_opts['params']))
        {
            if (strtoupper($c_opts['method']) == 'POST')
                $postfields = $c_opts['params'];
            else
                $url = $url.'?'.http_build_query($c_opts['params']);
        }

        // Start cURL request.
        $curl = curl_init($c_opts['url']);

        // Handle POST support.
        if (strtoupper($c_opts['method']) == 'POST')
            curl_setopt($curl, CURLOPT_POST, true);

        if (!empty($c_opts['referer']))
            curl_setopt($curl, CURLOPT_REFERER, $c_opts['referer']);

        if ($postfields)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 10); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $c_opts['useragent']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);

        // Set custom HTTP headers.
        if (!empty($c_opts['headers']))
            curl_setopt($curl, CURLOPT_HTTPHEADER, $c_opts['headers']);

        $return_raw = \PVL\Utilities::curl_exec_utf8($curl);
        // End cURL request.

        \PVL\Debug::endTimer('Make cURL Request');

        $error = curl_error($curl);
        if ($error)
            \PVL\Debug::log("Curl error: ".$error);

        if ($cache_time > 0)
            \DF\Cache::save($return_raw, $cache_name, array(), $cache_time);
        
        return trim($return_raw);
    }

    /* Calculate listener count from unique and current totals. */
    protected function getListenerCount($unique_listeners = 0, $current_listeners = 0)
    {
        $unique_listeners = (int)$unique_listeners;
        $current_listeners = (int)$current_listeners;

        if ($unique_listeners == 0 || $current_listeners == 0)
            return max($unique_listeners, $current_listeners);
        else
            return min($unique_listeners, $current_listeners);
    }

}