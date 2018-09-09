<?php
namespace App\Radio\Remote;

class SHOUTcast1 extends RemoteAbstract
{
    /** @inheritdoc */
    public function getPublicUrl(): string
    {
        return $this->_getRemoteUrl('/;stream.nsv');
    }

    /** @inheritdoc */
    public function updateNowPlaying(&$np): bool
    {
        $remote_stats_url = $this->_getRemoteUrl('/7.html');

        $return_raw = $this->getUrl($remote_stats_url);
        if (empty($return_raw)) {
            return false;
        }

        preg_match("/<body.*>(.*)<\/body>/smU", $return_raw, $return);
        [$current_listeners, , , , $unique_listeners, $bitrate, $title] = explode(',', $return[1], 7);

        $this->logger->debug('Remote ShoutCast 1 response.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName(), 'response' => $return[1]]);

        // Increment listener counts in the now playing data.
        $np['listeners']['current'] += (int)$current_listeners;
        $np['listeners']['unique'] += (int)$unique_listeners;
        $np['listeners']['total'] += $this->getListenerCount((int)$unique_listeners, (int)$current_listeners);

        // This is the first stream with metadata; provide it here.
        if ($np['meta']['status'] === 'offline') {
            $np['current_song'] = $this->getSongFromString($title, '-');

            $np['meta']['status'] = 'online';
            $np['meta']['bitrate'] = $bitrate;
        }

        return true;
    }
}
