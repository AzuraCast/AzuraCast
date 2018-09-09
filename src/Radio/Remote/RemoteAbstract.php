<?php
namespace App\Radio\Remote;

use App\Entity;
use App\Radio\Traits\AdapterCommon;
use GuzzleHttp\Client;
use Monolog\Logger;

abstract class RemoteAbstract
{
    use AdapterCommon;

    /** @var Entity\Station */
    protected $station;

    /** @var Entity\StationRemote */
    protected $remote;

    /** @var Logger */
    protected $logger;

    public function __construct(Client $http_client, Logger $logger)
    {
        $this->http_client = $http_client;
        $this->logger = $logger;
    }

    /**
     * @param Entity\Station $station
     */
    public function setStation(Entity\Station $station): void
    {
        $this->station = $station;
    }

    /**
     * @param Entity\StationRemote $remote
     */
    public function setRemote(Entity\StationRemote $remote): void
    {
        $this->remote = $remote;
    }

    /**
     * @return Entity\StationRemote
     */
    public function getRemote(): Entity\StationRemote
    {
        return $this->remote;
    }

    /**
     * @param $np
     * @return bool
     */
    public function updateNowPlaying(&$np): bool
    {
        return true;
    }

    /**
     * Return the likely "public" listen URL for the remote.
     *
     * @return string
     */
    public function getPublicUrl(): string
    {
        $custom_listen_url = $this->remote->getCustomListenUrl();

        return (!empty($custom_listen_url))
            ? $custom_listen_url
            : $this->_getRemoteUrl($this->remote->getMount());
    }

    /**
     * Format and return a URL for the remote path.
     *
     * @param string|null $custom_path
     * @return string
     */
    protected function _getRemoteUrl($custom_path = null): string
    {
        $parsed_url = parse_url($this->remote->getUrl());

        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';

        // Remove URL parts directing to statistics pages that users often specify (but don't need to).
        $filter_from_original = ['/status-json.xsl','/7.html','/stats'];
        $path = str_replace($filter_from_original, array_fill(0, count($filter_from_original), ''), $path);

        if ($custom_path !== null) {
            $path = '/' . ltrim($custom_path, '/');
        }

        return "$scheme$host$port$path";
    }
}
