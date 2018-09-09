<?php
namespace App\Radio\Remote;

class SHOUTcast2 extends RemoteAbstract
{
    /** @inheritdoc */
    public function updateNowPlaying(&$np): bool
    {
        $sid = (int)$this->remote->getMount() ?: 1;
        $remote_stats_url = $this->_getRemoteUrl('/stats?sid='.$sid);

        $return_raw = $this->getUrl($remote_stats_url);
        if (empty($return_raw)) {
            return false;
        }

        $current_data = \App\Export::xml_to_array($return_raw);
        $song_data = $current_data['SHOUTCASTSERVER'];

        $this->logger->debug('Remote ShoutCast 2 response.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName(), 'response' => $song_data]);

        // Increment listener counts in the now playing data.
        $u_list = (int)$song_data['UNIQUELISTENERS'];
        $c_list = (int)$song_data['CURRENTLISTENERS'];

        $np['listeners']['current'] += $c_list;
        $np['listeners']['unique'] += $u_list;
        $np['listeners']['total'] += $this->getListenerCount($u_list, $c_list);

        // This is the first stream with metadata; provide it here.
        if ($np['meta']['status'] === 'offline') {
            $np['current_song'] = $this->getSongFromString($song_data['SONGTITLE'], '-');

            $np['meta']['status'] = 'online';
            $np['meta']['bitrate'] = $song_data['BITRATE'];
            $np['meta']['format'] = $song_data['CONTENT'];
        }
    }
}
