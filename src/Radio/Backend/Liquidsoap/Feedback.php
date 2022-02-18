<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\SimpleCache\CacheInterface;

class Feedback
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected CacheInterface $cache
    ) {
    }

    /**
     * Queue an individual station for processing its "Now Playing" metadata.
     *
     * @param Entity\Station $station
     * @param array $extraMetadata
     */
    public function __invoke(Entity\Station $station, array $extraMetadata = []): void
    {
        // Process extra metadata sent by Liquidsoap (if it exists).
        if (empty($extraMetadata['media_id'])) {
            return;
        }

        $media = $this->em->find(Entity\StationMedia::class, $extraMetadata['media_id']);
        if (!$media instanceof Entity\StationMedia) {
            return;
        }

        $sq = $this->queueRepo->findRecentlyCuedSong($station, $media);

        if (null !== $sq) {
            // If there's an existing record, ensure it has all the proper metadata.
            if (null === $sq->getMedia()) {
                $sq->setMedia($media);
            }

            if (!empty($extraMetadata['playlist_id']) && null === $sq->getPlaylist()) {
                $playlist = $this->em->find(Entity\StationPlaylist::class, $extraMetadata['playlist_id']);
                if ($playlist instanceof Entity\StationPlaylist) {
                    $sq->setPlaylist($playlist);
                }
            }

            $sq->setSentToAutodj();
            $sq->setTimestampPlayed(time());

            $this->em->persist($sq);
            $this->em->flush();
        } else {
            // If not, store the feedback information in the temporary cache for later checking.
            $this->cache->set(
                $this->getFeedbackCacheName($station),
                $extraMetadata,
                3600
            );
        }
    }

    public function registerFromFeedback(
        Entity\Station $station,
        Entity\Interfaces\SongInterface $song
    ): ?Entity\SongHistory {
        $cacheKey = $this->getFeedbackCacheName($station);

        if (!$this->cache->has($cacheKey)) {
            return null;
        }

        $extraMetadata = (array)$this->cache->get($cacheKey);
        if ($song->getSongId() !== ($extraMetadata['song_id'] ?? null)) {
            return null;
        }

        $media = $this->em->find(Entity\StationMedia::class, $extraMetadata['media_id']);
        if (!$media instanceof Entity\StationMedia) {
            return null;
        }

        $history = new Entity\SongHistory($station, $media);
        $history->setMedia($media);

        if (!empty($extraMetadata['playlist_id'])) {
            $playlist = $this->em->find(Entity\StationPlaylist::class, $extraMetadata['playlist_id']);
            if ($playlist instanceof Entity\StationPlaylist) {
                $history->setPlaylist($playlist);
            }
        }

        return $history;
    }

    protected function getFeedbackCacheName(Entity\Station $station): string
    {
        return 'liquidsoap.feedback_' . $station->getIdRequired();
    }
}
