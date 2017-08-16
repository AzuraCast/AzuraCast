<?php
namespace AzuraCast\Radio\Frontend;

use App\Debug;
use App\Service\Curl;

abstract class FrontendAbstract extends \AzuraCast\Radio\AdapterAbstract
{
    /** @var bool Whether the station supports multiple mount points per station */
    protected $supports_mounts = true;

    public function supportsMounts()
    {
        return $this->supports_mounts;
    }

    /** @var bool Whether the station supports a full, unique listener count (including IP, user-agent, etc) */
    protected $supports_listener_detail = true;

    public function supportsListenerDetail()
    {
        return $this->supports_listener_detail;
    }

    public function getDefaultMounts()
    {
        return [
            [
                'name' => '/radio.mp3',
                'is_default' => 1,
                'enable_autodj' => 1,
                'autodj_format' => 'mp3',
                'autodj_bitrate' => 128,
            ]
        ];
    }

    public function getProgramName()
    {
        return 'station_' . $this->station->getId() . ':station_' . $this->station->getId() . '_frontend';
    }

    abstract public function getStreamUrl();

    abstract public function getAdminUrl();

    public function getPublicUrl()
    {
        $fe_config = (array)$this->station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        $settings_repo = $this->di['em']->getRepository('Entity\Settings');

        $base_url = $settings_repo->getSetting('base_url', 'localhost');
        $use_radio_proxy = $settings_repo->getSetting('use_radio_proxy', 0);

        // Web proxy support.
        if ((!APP_IN_PRODUCTION && !APP_INSIDE_DOCKER) || $use_radio_proxy) {
            return ((APP_IS_SECURE) ? 'https' : 'http') . '://' . $base_url . '/radio/' . $radio_port;
        } else {
            return 'http://' . $base_url . ':' . $radio_port;
        }
    }

    /* Fetch a remote URL. */
    protected function getUrl($url, $c_opts = null)
    {
        if ($c_opts === null) {
            $c_opts = [];
        }

        if (!isset($c_opts['url'])) {
            $c_opts['url'] = $url;
        }

        if (!isset($c_opts['timeout'])) {
            $c_opts['timeout'] = 4;
        }

        return Curl::request($c_opts);
    }

    public function getNowPlaying()
    {
        // Now Playing defaults.
        $np = [
            'current_song' => [
                'text' => 'Stream Offline',
                'title' => '',
                'artist' => '',
            ],
            'listeners' => [
                'current' => 0,
                'unique' => null,
                'total' => null,
            ],
            'meta' => [
                'status' => 'offline',
                'bitrate' => 0,
                'format' => '',
            ],
        ];

        // Merge station-specific info into defaults.
        $this->_getNowPlaying($np);

        // Update status code for offline stations, clean up song info for online ones.
        if ($np['current_song']['text'] == 'Stream Offline') {
            $np['meta']['status'] = 'offline';
        } else {
            array_walk($np['current_song'], [$this, '_cleanUpString']);
        }

        // Fill in any missing listener info.
        if ($np['listeners']['unique'] === null) {
            $np['listeners']['unique'] = $np['listeners']['current'];
        }

        if ($np['listeners']['total'] === null) {
            $np['listeners']['total'] = $np['listeners']['current'];
        }

        Debug::print_r($np);

        return $np;
    }

    /* Stub function for the process internal handler. */
    abstract protected function _getNowPlaying(&$np);

    protected function _cleanUpString(&$value)
    {
        $value = htmlspecialchars_decode($value);
        $value = trim($value);
    }

    /* Calculate listener count from unique and current totals. */
    protected function getListenerCount($unique_listeners = 0, $current_listeners = 0)
    {
        $unique_listeners = (int)$unique_listeners;
        $current_listeners = (int)$current_listeners;

        if ($unique_listeners == 0 || $current_listeners == 0) {
            return max($unique_listeners, $current_listeners);
        } else {
            return min($unique_listeners, $current_listeners);
        }
    }

    /* Return the artist and title from a string in the format "Artist - Title" */
    protected function getSongFromString($song_string, $delimiter = '-')
    {
        // Filter for CR AutoDJ
        $song_string = str_replace('AutoDJ - ', '', $song_string);

        // Fix ShoutCast 2 bug where 3 spaces = " - "
        $song_string = str_replace('   ', ' - ', $song_string);

        // Remove dashes or spaces on both sides of the name.
        $song_string = trim($song_string, " \t\n\r\0\x0B-");

        $string_parts = explode($delimiter, $song_string);

        // If not normally delimited, return "text" only.
        if (count($string_parts) == 1) {
            return ['text' => $song_string, 'artist' => '', 'title' => $song_string];
        }

        // Title is the last element, artist is all other elements (artists are far more likely to have hyphens).
        $title = trim(array_pop($string_parts));
        $artist = trim(implode($delimiter, $string_parts));

        return [
            'text' => $song_string,
            'artist' => $artist,
            'title' => $title,
        ];
    }

    /**
     * Log a message to console or to flash (if interactive session).
     *
     * @param $message
     */
    public function log($message, $class = 'info')
    {
        if (!empty(trim($message))) {
            parent::log(str_pad('Radio Frontend: ', 20, ' ', STR_PAD_RIGHT) . $message, $class);
        }
    }

    protected function _processCustomConfig($custom_config_raw)
    {
        $custom_config = [];

        if (substr($custom_config_raw, 0, 1) == '{') {
            $custom_config = @json_decode($custom_config_raw, true);
        } elseif (substr($custom_config_raw, 0, 1) == '<') {
            $reader = new \App\Xml\Reader;
            $custom_config = $reader->fromString('<icecast>' . $custom_config_raw . '</icecast>');
        }

        return $custom_config;
    }

    protected function _getRadioPort()
    {
        return (8000 + (($this->station->getId() - 1) * 10));
    }
}