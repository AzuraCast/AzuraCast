<?php
namespace PVL\VideoAdapter;

use Entity\Station;
use Entity\StationStream;

class AdapterAbstract
{
    /**
     * @var Station
     */
    protected $station;

    /**
     * @var StationStream
     */
    protected $stream;

    /**
     * @var string URL for stream data.
     */
    protected $stream_url;

    /**
     * @var string URL for now playing data.
     */
    protected $data_url;

    /**
     * @param StationStream $stream
     * @param Station $station
     */
    public function __construct(StationStream $stream, Station $station)
    {
        $this->station = $station;
        $this->stream = $stream;

        $this->stream_url = $stream->stream_url;
        $this->data_url = $stream->nowplaying_url;
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
                'thumbnail'     => \DF\Url::content('images/video_thumbnail.png'),
            ),
            'meta' => array(
                'status'        => 'offline',
                'listeners'     => 0,
            ),
        );

        if (!$this->stream->is_active)
            return $np;

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