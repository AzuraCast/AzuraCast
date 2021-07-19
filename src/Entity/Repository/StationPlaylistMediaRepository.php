<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;

class StationPlaylistMediaRepository extends Repository
{
    protected StationQueueRepository $queueRepo;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        StationQueueRepository $queueRepo
    ) {
        parent::__construct($em, $serializer, $environment, $logger);

        $this->queueRepo = $queueRepo;
    }

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
            $record = $this->repository->findOneBy(
                [
                    'media_id' => $media->getId(),
                    'playlist_id' => $playlist->getId(),
                ]
            );
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

        return $weight;
    }

    public function getHighestSongWeight(Entity\StationPlaylist $playlist): int
    {
        try {
            $highest_weight = $this->em->createQuery(
                <<<'DQL'
                    SELECT MAX(e.weight)
                    FROM App\Entity\StationPlaylistMedia e
                    WHERE e.playlist_id = :playlist_id
                DQL
            )->setParameter('playlist_id', $playlist->getId())
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $highest_weight = 1;
        }

        return (int)$highest_weight;
    }

    /**
     * Remove all playlist associations from the specified media object.
     *
     * @param Entity\StationMedia $media
     * @param Entity\Station|null $station
     *
     * @return Entity\StationPlaylist[] The IDs as keys and records as values for all affected playlists.
     */
    public function clearPlaylistsFromMedia(
        Entity\StationMedia $media,
        ?Entity\Station $station = null
    ): array {
        $affectedPlaylists = [];

        $playlists = $media->getPlaylists();
        if (null !== $station) {
            $playlists = $playlists->filter(
                function (Entity\StationPlaylistMedia $spm) use ($station) {
                    return $spm->getPlaylist()->getStation()->getId() === $station->getId();
                }
            );
        }

        foreach ($playlists as $spmRow) {
            $playlist = $spmRow->getPlaylist();
            $affectedPlaylists[$playlist->getId()] = $playlist;

            $this->queueRepo->clearForMediaAndPlaylist($media, $playlist);

            $this->em->remove($spmRow);
        }

        return $affectedPlaylists;
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
    public function setMediaOrder(Entity\StationPlaylist $playlist, array $mapping): void
    {
        $update_query = $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationPlaylistMedia e
                SET e.weight = :weight
                WHERE e.playlist_id = :playlist_id
                AND e.id = :id
            DQL
        )->setParameter('playlist_id', $playlist->getId());

        $this->em->transactional(
            function () use ($update_query, $mapping): void {
                foreach ($mapping as $id => $weight) {
                    $update_query->setParameter('id', $id)
                        ->setParameter('weight', $weight)
                        ->execute();
                }
            }
        );
    }

    /**
     * @return Entity\Api\StationPlaylistQueue[]
     */
    public function resetQueue(Entity\StationPlaylist $playlist, CarbonInterface $now = null): array
    {
        if ($playlist::SOURCE_SONGS !== $playlist->getSource()) {
            throw new InvalidArgumentException('Playlist must contain songs.');
        }

        if ($playlist::ORDER_SEQUENTIAL === $playlist->getOrder()) {
            $this->em->createQuery(
                <<<'DQL'
                    UPDATE App\Entity\StationPlaylistMedia spm
                    SET spm.is_queued = 1
                    WHERE spm.playlist = :playlist
                DQL
            )->setParameter('playlist', $playlist)
                ->execute();
        } elseif ($playlist::ORDER_SHUFFLE === $playlist->getOrder()) {
            $this->em->transactional(
                function () use ($playlist): void {
                    $allSpmRecordsQuery = $this->em->createQuery(
                        <<<'DQL'
                            SELECT spm.id
                            FROM App\Entity\StationPlaylistMedia spm
                            WHERE spm.playlist = :playlist
                            ORDER BY RAND()
                        DQL
                    )->setParameter('playlist', $playlist);

                    $updateSpmWeightQuery = $this->em->createQuery(
                        <<<'DQL'
                            UPDATE App\Entity\StationPlaylistMedia spm
                            SET spm.weight=:weight, spm.is_queued=1
                            WHERE spm.id = :id
                        DQL
                    );

                    $allSpmRecords = $allSpmRecordsQuery->toIterable([], $allSpmRecordsQuery::HYDRATE_SCALAR);
                    $weight = 1;

                    foreach ($allSpmRecords as $spmId) {
                        $updateSpmWeightQuery->setParameter('id', $spmId)
                            ->setParameter('weight', $weight)
                            ->execute();

                        $weight++;
                    }
                }
            );
        }

        $now = $now ?? CarbonImmutable::now($playlist->getStation()->getTimezoneObject());

        $playlist->setQueueResetAt($now->getTimestamp());
        $this->em->persist($playlist);
        $this->em->flush();

        return $this->getQueue($playlist);
    }

    /**
     * @return Entity\Api\StationPlaylistQueue[]
     */
    public function getQueue(Entity\StationPlaylist $playlist): array
    {
        if ($playlist::SOURCE_SONGS !== $playlist->getSource()) {
            throw new InvalidArgumentException('Playlist must contain songs.');
        }

        $queuedMediaQuery = $this->em->createQueryBuilder()
            ->select(['spm.id AS spm_id', 'sm.id', 'sm.song_id', 'sm.artist', 'sm.title'])
            ->from(Entity\StationMedia::class, 'sm')
            ->join('sm.playlists', 'spm')
            ->where('spm.playlist = :playlist')
            ->setParameter('playlist', $playlist);

        if ($playlist::ORDER_RANDOM === $playlist->getOrder()) {
            $queuedMediaQuery = $queuedMediaQuery->orderBy('RAND()');
        } else {
            $queuedMediaQuery = $queuedMediaQuery->andWhere('spm.is_queued = 1')
                ->orderBy('spm.weight', 'ASC');
        }

        $queuedMedia = $queuedMediaQuery->getQuery()->getArrayResult();

        return array_map(
            static function ($val): Entity\Api\StationPlaylistQueue {
                $record = new Entity\Api\StationPlaylistQueue();
                $record->spm_id = $val['spm_id'];
                $record->media_id = $val['id'];
                $record->song_id = $val['song_id'];
                $record->artist = $val['artist'] ?? '';
                $record->title = $val['title'] ?? '';

                return $record;
            },
            $queuedMedia
        );
    }

    public function isQueueCompletelyFilled(Entity\StationPlaylist $playlist): bool
    {
        if ($playlist::SOURCE_SONGS !== $playlist->getSource()) {
            return true;
        }

        if ($playlist::ORDER_RANDOM === $playlist->getOrder()) {
            return true;
        }

        $notQueuedMediaCount = $this->getCountPlaylistMediaBaseQuery($playlist)
            ->andWhere('spm.is_queued = 0')
            ->getQuery()
            ->getSingleScalarResult();

        if ($notQueuedMediaCount === 0) {
            return true;
        }

        return false;
    }

    public function isQueueEmpty(Entity\StationPlaylist $playlist): bool
    {
        if ($playlist::SOURCE_SONGS !== $playlist->getSource()) {
            return false;
        }

        if ($playlist::ORDER_RANDOM === $playlist->getOrder()) {
            return false;
        }

        $totalMediaCount = $this->getCountPlaylistMediaBaseQuery($playlist)
            ->getQuery()
            ->getSingleScalarResult();

        $notQueuedMediaCount = $this->getCountPlaylistMediaBaseQuery($playlist)
            ->andWhere('spm.is_queued = 0')
            ->getQuery()
            ->getSingleScalarResult();

        if ($notQueuedMediaCount === $totalMediaCount) {
            return true;
        }

        return false;
    }

    protected function getCountPlaylistMediaBaseQuery(Entity\StationPlaylist $playlist): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('count(spm.id)')
            ->from(Entity\StationMedia::class, 'sm')
            ->join('sm.playlists', 'spm')
            ->where('spm.playlist = :playlist')
            ->setParameter('playlist', $playlist);
    }
}
