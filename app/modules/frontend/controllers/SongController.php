<?php
namespace Modules\Frontend\Controllers;

use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;

class SongController extends BaseController
{
    public function indexAction()
    {
        $id = $this->getParam('id');

        if (empty($id))
            $this->redirectHome();

        $record = Song::find($id);

        if (!($record instanceof Song))
            throw new \DF\Exception\DisplayOnly('Song not found!');

        $song_info = array();
        $song_info['record'] = $record;

        // Get external provider information.
        $song_info['external'] = $record->getExternal();

        // Get album art and lyrics from all providers.
        $adapters = Song::getExternalAdapters();
        $external_fields = array('lyrics', 'purchase_url', 'description');

        foreach($external_fields as $field_name)
        {
            $song_info[$field_name] = NULL;
            foreach($adapters as $adapter_name => $adapter_class)
            {
                if (!empty($song_info['external'][$adapter_name][$field_name]))
                {
                    $song_info[$field_name] = $song_info['external'][$adapter_name][$field_name];
                    break;
                }
            }
        }

        if (!$song_info['image_url'])
            $song_info['image_url'] = \DF\Url::content('images/song_generic.png');

        $song_info['description'] = $this->_cleanUpText($song_info['description']);
        $song_info['lyrics'] = $this->_cleanUpText($song_info['lyrics']);

        // Get most recent playback information.
        $history_raw = $this->em->createQuery('
            SELECT sh, st
            FROM Entity\SongHistory sh JOIN sh.station st
            WHERE sh.song_id = :song_id AND st.category IN (:categories) AND sh.timestamp >= :threshold
            ORDER BY sh.timestamp DESC')
            ->setParameter('song_id', $record->id)
            ->setParameter('categories', array('audio', 'video'))
            ->setParameter('threshold', strtotime('-1 week'))
            ->getArrayResult();

        $history = array();
        $last_row = NULL;

        foreach($history_raw as $i => $row)
        {
            if ($last_row && $row['station_id'] == $last_row['station_id'])
            {
                $timestamp_diff = abs($row['timestamp'] - $last_row['timestamp']);
                if ($timestamp_diff < 60)
                    continue;
            }

            $history[] = $row;
            $last_row = $row;
        }

        $song_info['recent_history'] = $history;

        // Get requestable locations.
        $song_info['request_on'] = $this->em->createQuery('
            SELECT sm, st
            FROM Entity\StationMedia sm JOIN sm.station st
            WHERE sm.song_id = :song_id
            GROUP BY sm.station_id')
            ->setParameter('song_id', $record->id)
            ->getArrayResult();

        $this->view->song = $song_info;
    }

    protected function _cleanUpText($string)
    {
        $string = trim($string);
        return nl2br(strip_tags($string));
    }

}