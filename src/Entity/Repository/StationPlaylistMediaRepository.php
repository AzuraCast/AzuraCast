<?php

namespace App\Entity\Repository;

use App\Radio\Backend\Liquidsoap;
use App\Radio\PlaylistParser;
use Azura\Cache;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\NoResultException;
use App\Entity;

class StationPlaylistMediaRepository extends Repository
{
    /** @var int The time to live (in seconds) of cached playlist queues. */
    const CACHE_TTL = 43200;

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
    public function addMediaToPlaylist(Entity\StationMedia $media, Entity\StationPlaylist $playlist, $weight = 0): int
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

        if (($record instanceof Entity\StationPlaylistMedia)) {
            if ($weight != 0) {
                $record->setWeight($weight);
                $this->_em->persist($record);
            }
        } else {
            if ($weight === 0) {
                $weight = $this->getHighestSongWeight($playlist) + 1;
            }

            $record = new Entity\StationPlaylistMedia($playlist, $media);
            $record->setWeight($weight);
            $this->_em->persist($record);
        }

        // Add the newly added song into the cached queue.
        if ($playlist->getOrder() !== Entity\StationPlaylist::ORDER_RANDOM) {
            $cache_name = $this->_getCacheName($playlist->getId());
            $media_queue = (array)$this->cache->get($cache_name);

            if (!empty($media_queue)) {
                $media_queue[] = $media->getId();

                if ($playlist->getOrder() === Entity\StationPlaylist::ORDER_SHUFFLE) {
                    shuffle($media_queue);
                }

                $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);
            }
        }

        return $weight;
    }

    public function getHighestSongWeight(Entity\StationPlaylist $playlist): int
    {
        try {
            $highest_weight = $this->_em->createQuery('SELECT MAX(e.weight) FROM ' . $this->_entityName . ' e WHERE e.playlist_id = :playlist_id')
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
            $update_weight_query = $this->_em->createQuery('UPDATE '.$this->_entityName.' spm SET spm.weight=:weight '.
                'WHERE spm.playlist_id = :playlist_id AND spm.media_id = :media_id')
                ->setParameter('playlist_id', $playlist->getId());

            $media_ids = $this->_getPlayableMediaIds($playlist);
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
     */
    public function clearPlaylistsFromMedia(Entity\StationMedia $media)
    {
        $playlists = $this->_em->createQuery('SELECT e.playlist_id FROM '.$this->_entityName.' e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->getArrayResult();

        foreach($playlists as $row) {
            $this->clearMediaQueue($row['playlist_id']);
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

        // Clear the playback queue.
        $this->clearMediaQueue($playlist->getId());

        foreach($mapping as $media_id => $weight) {
            $update_query->setParameter('media_id', $media_id)
                ->setParameter('weight', $weight)
                ->execute();
        }
    }

    /**
     * Return a song from the cached playback queue for a playlist, if applicable.
     *
     * @param Entity\StationPlaylist $playlist
     * @return Entity\StationMedia|string|null
     */
    public function getQueuedSong(Entity\StationPlaylist $playlist)
    {
        if (Entity\StationPlaylist::SOURCE_REMOTE_URL === $playlist->getSource()) {
            return $this->_playRemoteUrl($playlist);
        }

        if ($playlist->getOrder() === Entity\StationPlaylist::ORDER_RANDOM) {
            $media_queue = $this->_getPlayableMediaIds($playlist);

            shuffle($media_queue);
            $media_id = array_pop($media_queue);
        } else {
            $cache_name = $this->_getCacheName($playlist->getId());
            $media_queue = (array)$this->cache->get($cache_name);

            if (empty($media_queue)) {
                $media_queue = $this->_getPlayableMediaIds($playlist);
            }

            $media_id = array_shift($media_queue);

            // Save the modified cache, sans the now-missing entry.
            $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);
        }

        return ($media_id)
            ? $this->_em->find(Entity\StationMedia::class, $media_id)
            : null;
    }

    protected function _getPlayableMediaIds(Entity\StationPlaylist $playlist): array
    {
        $all_media = $this->_em->createQuery('SELECT sm.id FROM '.Entity\StationMedia::class.' sm
            JOIN sm.playlist_items spm
            WHERE spm.playlist_id = :playlist_id
            ORDER BY spm.weight ASC')
            ->setParameter('playlist_id', $playlist->getId())
            ->getArrayResult();

        $media_queue = [];
        foreach($all_media as $media_row) {
            $media_queue[] = $media_row['id'];
        }

        return $media_queue;
    }

    protected function _playRemoteUrl(Entity\StationPlaylist $playlist): ?string
    {
        $remote_type = $playlist->getRemoteType() ?? Entity\StationPlaylist::REMOTE_TYPE_STREAM;

        // Handle a raw stream URL of possibly indeterminate length.
        if (Entity\StationPlaylist::REMOTE_TYPE_STREAM === $remote_type) {
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            if (Entity\StationPlaylist::TYPE_SCHEDULED === $playlist->getType()) {
                $duration = $playlist->getScheduleDuration();
                return 'annotate:duration="'.Liquidsoap::toFloat($duration).'":'.$playlist->getRemoteUrl();
            }

            return $playlist->getRemoteUrl();
        }

        // Handle a remote playlist containing songs or streams.
        $cache_name = $this->_getCacheName($playlist->getId());
        $media_queue = (array)$this->cache->get($cache_name);

        if (empty($media_queue)) {
            $playlist_raw = file_get_contents($playlist->getRemoteUrl());
            $media_queue = PlaylistParser::getSongs($playlist_raw);
        }

        if (!empty($media_queue)) {
            $media_id = array_shift($media_queue);
        } else {
            $media_id = null;
        }

        // Save the modified cache, sans the now-missing entry.
        $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);

        return $media_id;
    }

    /**
     * @param int $playlist_id
     */
    public function clearMediaQueue(int $playlist_id)
    {
        $this->cache->remove($this->_getCacheName($playlist_id));
    }

    /**
     * Get the cache name for the given playlist.
     *
     * @param int $playlist_id
     * @return string
     */
    protected function _getCacheName(int $playlist_id): string
    {
        return 'autodj/playlist_'.$playlist_id;
    }
}
