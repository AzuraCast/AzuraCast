<?php
namespace AzuraCast\Radio\Frontend;

class Remote extends FrontendAbstract
{
    protected $supports_mounts = true;
    protected $supports_listener_detail = false;

    /**
     * @inheritdoc
     */
    protected function _getNowPlaying(&$np, $payload = null, $include_clients = true)
    {
        $default_mount = $this->_getDefaultMount();
        $default_mount_np = $this->_getMountNowPlayingData($default_mount);

        if (!empty($default_mount_np['artist'])) {
            $np['current_song'] = [
                'artist' => $default_mount_np['artist'],
                'title' => $default_mount_np['title'],
                'text' => $default_mount_np['artist'] . ' - ' . $default_mount_np['title'],
            ];
        } else {
            $np['current_song'] = $this->getSongFromString($default_mount_np['title'], ' - ');
        }

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $default_mount_np['bitrate'];
        $np['meta']['format'] = $default_mount_np['server_type'];

        $np['listeners']['current'] = (int)$default_mount_np['listeners'];

        return false;
    }

    public function read() {}

    public function write() {}

    public function isRunning()
    {
        return true;
    }

    public function getStreamUrl()
    {
        $default_mount = $this->_getDefaultMount();
        return $this->getUrlForMount($default_mount);
    }

    public function getStreamUrls()
    {
        $mounts = $this->_getMounts();

        $stream_urls = [];
        foreach($mounts as $mount) {
            $stream_urls[] = $this->getUrlForMount($mount);
        }

        return $stream_urls;
    }

    public function getPublicUrl()
    {
        return $this->_getMountPublicUrl($this->_getDefaultMount());
    }

    public function getUrlForMount($mount)
    {
        $np = $this->_getMountNowPlayingData($mount);
        return $np['listenurl'] ?? '';
    }

    public function getAdminUrl()
    {
        $mount = $this->_getDefaultMount();
        $remote_type = ($mount instanceof \Entity\StationMount) ? $mount->getRemoteType() : $mount['remote_type'];

        switch ($remote_type) {
            case 'icecast':
                return $this->_getMountPublicUrl($mount, '/admin/');
                break;

            case 'shoutcast1':
            case 'shoutcast2':
                return $this->_getMountPublicUrl($mount, '/admin.cgi');
                break;
        }

        return false;
    }

    protected function _getDefaultMount()
    {
        $mounts = $this->station->getMounts();

        if ($mounts->count() > 0) {
            foreach($mounts as $mount) {
                /** @var \Entity\StationMount $mount */
                if ($mount->getIsDefault()) {
                    return $mount;
                }
            }
        }

        return (array)$this->station->getFrontendConfig();
    }

    protected function _getMounts()
    {
        $mounts = $this->station->getMounts();

        if ($mounts->count() == 0) {
            return [ (array)$this->station->getFrontendConfig() ];
        } else {
            return $mounts;
        }
    }

    protected function _getMountNowPlayingData($mount)
    {
        if ($mount instanceof \Entity\StationMount) {
            $settings = [
                'remote_type'   => $mount->getRemoteType(),
                'remote_url'    => $mount->getRemoteUrl(),
                'remote_mount'  => $mount->getRemoteMount(),
            ];
        } else {
            $settings = (array)$mount;
        }

        switch ($settings['remote_type'])
        {
            case 'icecast':
                $mount_url = (!empty($settings['remote_mount'])) ? '?mount='.$settings['remote_mount'] : '';

                $remote_stats_url = $this->_getMountPublicUrl($mount, '/status-json.xsl'.$mount_url);
                $return_raw = $this->getUrl($remote_stats_url);

                if (!$return_raw) {
                    return false;
                }

                $return = @json_decode($return_raw, true);

                $this->logger->debug('Remote IceCast response.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName(), 'response' => $return]);

                if (!$return || !isset($return['icestats']['source'])) {
                    return false;
                }

                $sources = $return['icestats']['source'];

                if (empty($sources)) {
                    return false;
                }

                if (key($sources) === 0) {
                    $mounts = $sources;
                } else {
                    $mounts = [$sources];
                }

                if (count($mounts) == 0) {
                    return false;
                }

                if (count($mounts) > 1) {
                    $mounts = array_filter($mounts, function ($mount_row) {
                        return (!empty($mount_row['title']) || !empty($mount_row['artist']));
                    });

                    // Sort in descending order of listeners.
                    usort($mounts, function ($a, $b) {
                        $a_list = (int)$a['listeners'];
                        $b_list = (int)$b['listeners'];

                        if ($a_list == $b_list) {
                            return 0;
                        } else {
                            return ($a_list > $b_list) ? -1 : 1;
                        }
                    });
                }

                return reset($mounts);
                break;

            case 'shoutcast1':
                $remote_stats_url = $this->_getMountPublicUrl($mount, '/7.html');
                $return_raw = $this->getUrl($remote_stats_url);

                if (empty($return_raw)) {
                    return false;
                }

                preg_match("/<body.*>(.*)<\/body>/smU", $return_raw, $return);
                $parts = explode(",", $return[1], 7);

                $this->logger->debug('Remote ShoutCast 1 response.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName(), 'response' => $parts]);

                return [
                    'title' => $parts[6],
                    'bitrate' => $parts[5],
                    'listenurl' => $this->_getMountPublicUrl($mount, '/;stream.nsv'),
                    'server_type' => 'audio/mpeg',
                    'listeners' => $this->getListenerCount((int)$parts[4], (int)$parts[0]),
                ];
                break;

            case 'shoutcast2':
                $sid = (int)$settings['remote_mount'] ?: 1;

                $remote_stats_url = $this->_getMountPublicUrl($mount, '/stats?sid='.$sid);
                $return_raw = $this->getUrl($remote_stats_url);

                if (empty($return_raw)) {
                    return false;
                }

                $current_data = \App\Export::xml_to_array($return_raw);
                $song_data = $current_data['SHOUTCASTSERVER'];

                $this->logger->debug('Remote ShoutCast 2 response.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName(), 'response' => $song_data]);

                return [
                    'title' => $song_data['SONGTITLE'],
                    'bitrate' => $song_data['BITRATE'],
                    'listenurl' => $this->_getMountPublicUrl($mount, $song_data['STREAMPATH']),
                    'server_type' => $song_data['CONTENT'],
                    'listeners' => $this->getListenerCount((int)$song_data['UNIQUELISTENERS'],
                        (int)$song_data['CURRENTLISTENERS']),
                ];
                break;
        }

        return false;
    }

    protected function _getMountPublicUrl($mount, $custom_path = null)
    {
        $remote_url = ($mount instanceof \Entity\StationMount) ? $mount->getRemoteUrl() : $mount['remote_url'];
        $parsed_url = parse_url($remote_url);

        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';

        $filter_from_original = ['/status-json.xsl','/7.html','/stats'];
        $path = str_replace($filter_from_original, array_fill(0, count($filter_from_original), ''), $path);

        if ($custom_path !== null)
            $path = $custom_path;

        return "$scheme$host$port$path";
    }
}