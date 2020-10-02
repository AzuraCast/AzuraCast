<?php
namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Doctrine\ORM\NoResultException;
use RuntimeException;

class StationPlaylistMediaRepository extends Repository
{
    /**
     * Add the specified media to the specified playlist.
     * Must flush the EntityManager after using.
     *
     * @param Entity\StationMedia $media
     * @param Entity\StationPlaylist $playlist
     * @param int $weight
     *
     * @return int The weight assigned to the newly added record.
     */
    public function addMediaToPlaylist(
        Entity\StationMedia $media,
        Entity\StationPlaylist $playlist,
        int $weight = 0
    ): int {
        if ($playlist->getSource() !== Entity\StationPlaylist::SOURCE_SONGS) {
            throw new RuntimeException('This playlist is not meant to contain songs!');
        }

        // Only update existing record for random-order playlists.
        if ($playlist->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL) {
            $record = $this->repository->findOneBy([
                'media_id' => $media->getId(),
                'playlist_id' => $playlist->getId(),
            ]);
        } else {
            $record = null;
        }

        if ($record instanceof Entity\StationPlaylistMedia) {
            if (0 !== $weight) {
                $record->setWeight($weight);
                $this->em->persist($record);
            }
        } else {
            if (0 === $weight) {
                $weight = $this->getHighestSongWeight($playlist) + 1;
            }

            $record = new Entity\StationPlaylistMedia($playlist, $media);
            $record->setWeight($weight);
            $this->em->persist($record);
        }

        // Add the newly added song into the cached queue.
        $playlist->setQueue(null);
        $this->em->persist($playlist);

        return $weight;
    }

    public function getHighestSongWeight(Entity\StationPlaylist $playlist): int
    {
        try {
            $highest_weight = $this->em->createQuery(/** @lang DQL */ 'SELECT 
                MAX(e.weight) 
                FROM App\Entity\StationPlaylistMedia e 
                WHERE e.playlist_id = :playlist_id')
                ->setParameter('playlist_id', $playlist->getId())
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $highest_weight = 1;
        }

        return (int)$highest_weight;
    }

    /**
     * Remove all playlist associations from the specified media object.
     *
     * @param Entity\StationMedia $media
     *
     * @return array The IDs and records for all affected playlists.
     */
    public function clearPlaylistsFromMedia(Entity\StationMedia $media): array
    {
        $affected_playlists = [];
        $playlists = $this->em->createQuery(/** @lang DQL */ 'SELECT e, p 
            FROM App\Entity\StationPlaylistMedia e JOIN e.playlist p 
            WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();

        foreach ($playlists as $row) {
            /** @var Entity\StationPlaylistMedia $row */
            $playlist = $row->getPlaylist();
            $affected_playlists[$playlist->getId()] = $playlist->getId();
        }

        // Clear the playback queue.
        if (!empty($affected_playlists)) {
            $this->em->createQuery(/** @lang DQL */ 'UPDATE App\Entity\StationPlaylist sp
            SET sp.queue=null WHERE sp.id IN (:ids)')
                ->setParameter('ids', array_keys($affected_playlists))
                ->execute();
        }

        $this->em->createQuery(/** @lang DQL */ 'DELETE 
            FROM App\Entity\StationPlaylistMedia e
            WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();

        return $affected_playlists;
    }

    /**
     * Set the order of the media, specified as
     * [
     *    media_id => new_weight,
     *    ...
     * ]
     *
     * @param Entity\StationPlaylist $playlist
     * @param array $mapping
     */
    public function setMediaOrder(Entity\StationPlaylist $playlist, $mapping): void
    {
        $update_query = $this->em->createQuery(/** @lang DQL */ 'UPDATE 
            App\Entity\StationPlaylistMedia e 
            SET e.weight = :weight
            WHERE e.playlist_id = :playlist_id 
            AND e.id = :id')
            ->setParameter('playlist_id', $playlist->getId());

        foreach ($mapping as $id => $weight) {
            $update_query->setParameter('id', $id)
                ->setParameter('weight', $weight)
                ->execute();
        }

        // Clear the playback queue.
        $playlist->setQueue(null);
        $this->em->persist($playlist);
        $this->em->flush();
    }

    public function getPlayableMedia(Entity\StationPlaylist $playlist): array
    {
        $all_media = $this->em->createQuery(/** @lang DQL */ 'SELECT 
            sm.id, sm.song_id, sm.artist, sm.title
            FROM App\Entity\StationMedia sm
            JOIN sm.playlists spm
            WHERE spm.playlist_id = :playlist_id
            ORDER BY spm.weight ASC')
            ->setParameter('playlist_id', $playlist->getId())
            ->getArrayResult();

        if ($playlist->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL) {
            shuffle($all_media);
        }

        $media_queue = [];
        foreach ($all_media as $media_row) {
            $media_queue[$media_row['id']] = $media_row;
        }

        return $media_queue;
    }
}
