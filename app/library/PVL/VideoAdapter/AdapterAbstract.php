<?php
namespace PVL\VideoAdapter;

use \Entity\Video;

class AdapterAbstract
{
    protected $station;

    protected $stream_url;
    protected $data_url;

    /**
     * @param Video $station
     */
    public function __construct(Video $station)
    {
        $this->station = $station;

        $this->stream_url = $station->stream_url;
        $this->data_url = $station->nowplaying_url;
    }

    /**
     * Indicates whether this adapter can handle this type of stream URL.
     * Must be overridden by the adapter class.
     *
     * @return bool
     */
    public function canHandle()
    {
        return false;
    }

    /* Master processing and cleanup. */
    public function process()
    {
        // Now Playing defaults.
        $np = array(
            'on_air' => array(
                'text'          => 'Stream Offline',
                'thumbnail'     => '',
            ),
            'meta' => array(
                'status'        => 'offline',
                'listeners'     => 0,
            ),
        );

        // Merge station-specific info into defaults.
        $this->_process($np);

        // Update status code for offline stations, clean up song info for online ones.
        if ($np['on_air']['text'] == 'Stream Offline')
            $np['meta']['status'] = 'offline';

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
    protected function getUrl(array $c_opts = null)
    {
        if (!isset($c_opts['url']))
            $c_opts['url'] = $this->data_url;

        return \PVL\Service\Curl::request($c_opts);
    }
}