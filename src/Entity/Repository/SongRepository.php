<?php
namespace Entity\Repository;

use Entity;

class SongRepository extends \App\Doctrine\Repository
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

    public function getOrCreate($song_info, $is_radio_play = false)
    {
        $song_hash = Entity\Song::getSongHash($song_info);

        $obj = $this->getById($song_hash);

        if ($obj instanceof Entity\Song) {
            if ($is_radio_play) {
                $obj->last_played = time();
                $obj->play_count += 1;
            }

            $this->_em->persist($obj);
            $this->_em->flush();

            return $obj;
        } else {
            if (!is_array($song_info)) {
                $song_info = ['text' => $song_info];
            }

            $obj = new Entity\Song;
            $obj->id = $song_hash;

            if (empty($song_info['text'])) {
                $song_info['text'] = $song_info['artist'] . ' - ' . $song_info['title'];
            }

            $obj->text = $song_info['text'];
            $obj->title = $song_info['title'];
            $obj->artist = $song_info['artist'];

            if (isset($song_info['image_url'])) {
                $obj->image_url = $song_info['image_url'];
            }

            if ($is_radio_play) {
                $obj->last_played = time();
                $obj->play_count = 1;
            }

            $this->_em->persist($obj);
            $this->_em->flush();

            return $obj;
        }
    }

    /**
     * Return a song by its ID, including resolving merged song IDs.
     *
     * @param $song_hash
     * @return null|object
     */
    public function getById($song_hash)
    {
        $record = $this->find($song_hash);

        if ($record instanceof Entity\Song) {
            if (!empty($record->merge_song_id)) {
                return $this->getById($record->merge_song_id);
            } else {
                return $record;
            }
        }

        return null;
    }
}