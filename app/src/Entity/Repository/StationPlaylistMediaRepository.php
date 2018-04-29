<?php

namespace Entity\Repository;

use Doctrine\ORM\NoResultException;
use Entity;

class StationPlaylistMediaRepository extends BaseRepository
{
    /**
     * Add the specified media to the specified playlist.
     * Must flush the EntityManager after using.
     *
     * @param Entity\StationMedia $media
     * @param Entity\StationPlaylist $playlist
     * @return int The weight assigned to the newly added record.
     */
    public function addMediaToPlaylist(Entity\StationMedia $media, Entity\StationPlaylist $playlist, $weight = 0): int
    {
        $record = $this->findOneBy([
            'media_id' => $media->getId(),
            'playlist_id' => $playlist->getId(),
        ]);

        if (($record instanceof Entity\StationPlaylistMedia)) {
            if ($weight != 0) {
                $record->setWeight($weight);
                $this->_em->persist($record);
            }
        } else {
            if ($weight === 0) {
                try {
                    $highest_weight = $this->_em->createQuery('SELECT MAX(e.weight) FROM ' . $this->_entityName . ' e WHERE e.playlist_id = :playlist_id')
                        ->setParameter('playlist_id', $playlist->getId())
                        ->getSingleScalarResult();
                } catch (NoResultException $e) {
                    $highest_weight = 1;
                }

                $weight = $highest_weight + 1;
            }

            $record = new Entity\StationPlaylistMedia($playlist, $media);
            $record->setWeight($weight);
            $this->_em->persist($record);
        }

        return $weight;
    }

    /**
     * Remove all playlist associations from the specified media object.
     *
     * @param Entity\StationMedia $media
     */
    public function clearPlaylistsFromMedia(Entity\StationMedia $media)
    {
        $this->_em->createQuery('DELETE FROM '.$this->_entityName.' e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();
    }
}