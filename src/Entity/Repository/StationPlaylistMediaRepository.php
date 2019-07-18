<?php

namespace App\Entity\Repository;

use App\Radio\AutoDJ;
use Azura\Cache;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\NoResultException;
use App\Entity;

class StationPlaylistMediaRepository extends Repository
{
    /** @var Cache */
    protected $cache;

    public function __construct(
        EntityManagerInterface $em,
        Mapping\ClassMetadata $class,
        Cache $cache
    ) {
        parent::__construct($em, $class);

        $this->cache = $cache;
    }

    /**
     * Add the specified media to the specified playlist.
     * Must flush the EntityManager after using.
     *
     * @param Entity\StationMedia $media
     * @param Entity\StationPlaylist $playlist
     * @param int $weight
     * @return int The weight assigned to the newly added record.
     */
    public function addMediaToPlaylist(Entity\StationMedia $media, Entity\StationPlaylist $playlist, int $weight = 0): int
    {
        if ($playlist->getSource() !== Entity\StationPlaylist::SOURCE_SONGS) {
            throw new \Exception('This playlist is not meant to contain songs!');
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

        if ($record instanceof Entity\StationPlaylistMedia) {
            if (0 !== $weight) {
                $record->setWeight($weight);
                $this->_em->persist($record);
            }
        } else {
            if (0 === $weight) {
                $weight = $this->getHighestSongWeight($playlist) + 1;
            }

            $record = new Entity\StationPlaylistMedia($playlist, $media);
            $record->setWeight($weight);
            $this->_em->persist($record);
        }

        // Add the newly added song into the cached queue.
        if ($playlist->getOrder() !== Entity\StationPlaylist::ORDER_RANDOM) {
            $cache_name = AutoDJ::getPlaylistCacheName($playlist->getId());
            $media_queue = (array)$this->cache->get($cache_name);

            if (!empty($media_queue)) {
                $media_queue[] = $media->getId();

                if ($playlist->getOrder() === Entity\StationPlaylist::ORDER_SHUFFLE) {
                    shuffle($media_queue);
                }

                $this->cache->set($media_queue, $cache_name, AutoDJ::CACHE_TTL);
            }
        }

        return $weight;
    }

    public function getHighestSongWeight(Entity\StationPlaylist $playlist): int
    {
        try {
            $highest_weight = $this->_em->createQuery(/** @lang DQL */'SELECT 
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
     * "Shuffle" the weights of all media in a shuffled playlist.
     *
     * @param Entity\StationPlaylist $playlist
     */
    public function reshuffleMedia(Entity\StationPlaylist $playlist): void
    {
        if ($playlist->getOrder() !== Entity\StationPlaylist::ORDER_SHUFFLE) {
            return;
        }
        
        $this->_em->beginTransaction();

        try {
            $update_weight_query = $this->_em->createQuery(/** @lang DQL */'UPDATE App\Entity\StationPlaylistMedia spm 
                SET spm.weight=:weight 
                WHERE spm.playlist_id = :playlist_id 
                AND spm.media_id = :media_id')
                ->setParameter('playlist_id', $playlist->getId());

            $media_ids = array_keys($this->getPlayableMedia($playlist));
            shuffle($media_ids);

            $new_weight = 1;
            foreach($media_ids as $media_id) {
                $update_weight_query
                    ->setParameter('media_id', $media_id)
                    ->setParameter('weight', $new_weight)
                    ->execute();

                $new_weight++;
            }
            
            $this->_em->commit();
        } catch (\Exception $exception) {
            $this->_em->rollback();
        }
    }

    /**
     * Remove all playlist associations from the specified media object.
     *
     * @param Entity\StationMedia $media
     * @return array The IDs and records for all affected playlists.
     */
    public function clearPlaylistsFromMedia(Entity\StationMedia $media): array
    {
        $affected_playlists = [];
        $playlists = $this->_em->createQuery(/** @lang DQL */'SELECT e, p 
            FROM App\Entity\StationPlaylistMedia e JOIN e.playlist p 
            WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();

        foreach($playlists as $row) {
            /** @var Entity\StationPlaylistMedia $row */
            $playlist = $row->getPlaylist();

            $affected_playlists[$playlist->getId()] = $playlist;
            $this->clearMediaQueue($playlist->getId());
        }

        $this->_em->createQuery(/** @lang DQL */'DELETE 
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
        $update_query = $this->_em->createQuery(/** @lang DQL */'UPDATE 
            App\Entity\StationPlaylistMedia e 
            SET e.weight = :weight
            WHERE e.playlist_id = :playlist_id 
            AND e.id = :id')
            ->setParameter('playlist_id', $playlist->getId());

        // Clear the playback queue.
        $this->clearMediaQueue($playlist->getId());

        foreach($mapping as $id => $weight) {
            $update_query->setParameter('id', $id)
                ->setParameter('weight', $weight)
                ->execute();
        }
    }

    public function getPlayableMedia(Entity\StationPlaylist $playlist): array
    {
        $all_media = $this->_em->createQuery(/** @lang DQL */ 'SELECT 
            sm.id, sm.artist, sm.title
            FROM App\Entity\StationMedia sm
            JOIN sm.playlists spm
            WHERE spm.playlist_id = :playlist_id
            ORDER BY spm.weight ASC')
            ->setParameter('playlist_id', $playlist->getId())
            ->getArrayResult();

        $media_queue = [];
        foreach($all_media as $media_row) {
            $media_queue[$media_row['id']] = $media_row;
        }

        return $media_queue;
    }

    /**
     * @param int $playlist_id
     */
    public function clearMediaQueue(int $playlist_id): void
    {
        $this->cache->remove(AutoDJ::getPlaylistCacheName($playlist_id));
    }
}
