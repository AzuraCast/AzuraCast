<?php
use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;

class SongController extends \DF\Controller\Action
{
    public function indexAction()
    {
        $id = $this->getParam('id');
        $record = Song::find($id);

        if (!($record instanceof Song))
            throw new \DF\Exception\DisplayOnly('Song not found!');

        $song_info = array();
        $song_info['record'] = $record;

        // Get external provider information.
        $song_info['external'] = $record->getExternal();

        // Get album art and lyrics from all providers.
        $adapters = Song::getExternalAdapters();
        $external_fields = array('image_url', 'lyrics', 'purchase_url', 'description');

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

        // Get most recent playback information.
        $song_info['recent_history'] = $this->em->createQuery('
            SELECT sh, st
            FROM Entity\SongHistory sh JOIN sh.station st
            WHERE sh.song_id = :song_id')
            ->setParameter('song_id', $record->id)
            ->setMaxResults(20)
            ->getArrayResult();

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

}