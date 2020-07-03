<?php
namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use NowPlaying\Result\CurrentSong;

class SongRepository extends Repository
{
    /**
     * Retrieve an existing Song entity or create a new one.
     *
     * @param CurrentSong|array|string $song_info
     * @param bool $is_radio_play
     *
     * @return Entity\Song
     */
    public function getOrCreate($song_info, $is_radio_play = false): Entity\Song
    {
        if ($song_info instanceof CurrentSong) {
            $song_info = [
                'text' => $song_info->text,
                'artist' => $song_info->artist,
                'title' => $song_info->title,
            ];
        } elseif (!is_array($song_info)) {
            $song_info = ['text' => $song_info];
        }

        $song_hash = Entity\Song::getSongHash($song_info);

        $obj = $this->repository->find($song_hash);

        if (!($obj instanceof Entity\Song)) {
            $obj = new Entity\Song($song_info);
        }

        if ($is_radio_play) {
            $obj->played();
        }

        $this->em->persist($obj);
        $this->em->flush();

        return $obj;
    }
}
