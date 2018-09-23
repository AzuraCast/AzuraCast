<?php

namespace App\Entity\Repository;

use App\Radio\AutoDJ;
use Doctrine\ORM\NoResultException;
use App\Entity;

class StationPlaylistMediaRepository extends BaseRepository
{
    /** @var AutoDJ */
    protected $autodj;

    public function setAutoDJ(AutoDJ $autodj)
    {
        $this->autodj = $autodj;
    }

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
        if ($playlist->getSource() !== Entity\StationPlaylist::SOURCE_SONGS) {
            return false;
        }

        // Only update existing record for random-order playlists.
        if ($playlist->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL) {
            $record = $this->findOneBy([
                'media_id' => $media->getId(),
                'playlist_id' => $playlist->getId(),
            ]);
        } else {
            $record = null;
        }

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

        if ($this->autodj instanceof AutoDJ) {
            $this->autodj->addMediaToPlaylist($media, $playlist);
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
        if ($this->autodj instanceof AutoDJ) {
            $playlists = $this->_em->createQuery('SELECT e.playlist_id FROM '.$this->_entityName.' e WHERE e.media_id = :media_id')
                ->setParameter('media_id', $media->getId())
                ->getArrayResult();

            foreach($playlists as $row) {
                $this->autodj->clearPlaybackCache($row['playlist_id']);
            }
        }

        $this->_em->createQuery('DELETE FROM '.$this->_entityName.' e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();
    }

    /**
     * Set the order of the media, specified as
     * [
     *    media_id => new_weight,
     *    ...
     * ]
     *
     * @param Entity\StationPlaylist $playlist
     * @param $mapping
     */
    public function setMediaOrder(Entity\StationPlaylist $playlist, $mapping)
    {
        $update_query = $this->_em->createQuery('UPDATE '.$this->_entityName.' e 
            SET e.weight = :weight
            WHERE e.playlist_id = :playlist_id AND e.media_id = :media_id')
            ->setParameter('playlist_id', $playlist->getId());

        if ($this->autodj instanceof AutoDJ) {
            $this->autodj->clearPlaybackCache($playlist->getId());
        }

        foreach($mapping as $media_id => $weight) {
            $update_query->setParameter('media_id', $media_id)
                ->setParameter('weight', $weight)
                ->execute();
        }
    }
}
