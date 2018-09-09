<?php
namespace App\Radio\Remote;

class Icecast extends RemoteAbstract
{
    public function updateNowPlaying(&$np): bool
    {
        $mount_url = (!empty($this->remote->getMount())) ? '?mount='.$this->remote->getMount() : '';

        $remote_stats_url = $this->_getRemoteUrl('/status-json.xsl'.$mount_url);
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

        $mounts = key($sources) === 0 ? $sources : [$sources];
        if (count($mounts) === 0) {
            return false;
        }

        // Remove any stations that are currently offline.
        $mounts = array_filter($mounts, function ($mount_row) {
            return (!empty($mount_row['title']) || !empty($mount_row['artist']));
        });

        // Increment listener counts in the now playing data.
        foreach($mounts as $mount) {
            $np['listeners']['current'] += (int)$mount['listeners'];
            $np['listeners']['total'] += (int)$mount['listeners'];
        }

        // This is the first stream with metadata; provide it here.
        if ($np['meta']['status'] === 'offline' && count($mounts) > 0) {
            // Sort in descending order of listeners.
            if (count($mounts) > 1) {
                usort($mounts, function ($a, $b) {
                    $a_list = (int)$a['listeners'];
                    $b_list = (int)$b['listeners'];

                    if ($a_list === $b_list) {
                        return 0;
                    }

                    return ($a_list > $b_list) ? -1 : 1;
                });
            }

            $mount = array_shift($mounts);

            $np['current_song'] = $this->getCurrentSong($mount, ' - ');
            $np['meta']['status'] = 'online';
            $np['meta']['bitrate'] = $mount['bitrate'];
            $np['meta']['format'] = $mount['server_type'];
        }

        return true;
    }
}
