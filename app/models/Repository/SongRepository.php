<?php
namespace Entity\Repository;

use Entity;

class SongRepository extends BaseRepository
{
    /**
     * Get a list of all song IDs.
     *
     * @return array
     */
    public function getIds()
    {
        $ids_raw = $this->_em->createQuery('SELECT s.id FROM ' . $this->_entityName . ' s')
            ->getArrayResult();

        return \Packaged\Helpers\Arrays::ipull($ids_raw, 'id');
    }

    /**
     * Retrieve an existing Song entity or create a new one.
     *
     * @param $song_info
     * @param bool $is_radio_play
     * @return Entity\Song
     */
    public function getOrCreate($song_info, $is_radio_play = false): Entity\Song
    {
        $song_hash = Entity\Song::getSongHash($song_info);

        $obj = $this->find($song_hash);

        if (!($obj instanceof Entity\Song)) {
            if (!is_array($song_info)) {
                $song_info = ['text' => $song_info];
            }

            $obj = new Entity\Song($song_info);
        }

        if ($is_radio_play) {
            $obj->played();
        }

        $this->_em->persist($obj);
        $this->_em->flush();

        return $obj;
    }
}