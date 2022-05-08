<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class FeedbackCommand extends AbstractCommand
{
    public function __construct(
        Logger $logger,
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected CacheInterface $cache
    ) {
        parent::__construct($logger);
    }

    protected function doRun(Entity\Station $station, bool $asAutoDj = false, array $payload = []): bool
    {
        // Process extra metadata sent by Liquidsoap (if it exists).
        if (empty($payload['media_id'])) {
            throw new RuntimeException('No payload provided.');
        }

        $media = $this->em->find(Entity\StationMedia::class, $payload['media_id']);
        if (!$media instanceof Entity\StationMedia) {
            throw new RuntimeException('Media ID does not exist for station.');
        }

        $sq = $this->queueRepo->findRecentlyCuedSong($station, $media);

        if (null !== $sq) {
            // If there's an existing record, ensure it has all the proper metadata.
            if (null === $sq->getMedia()) {
                $sq->setMedia($media);
            }

            if (!empty($payload['playlist_id']) && null === $sq->getPlaylist()) {
                $playlist = $this->em->find(Entity\StationPlaylist::class, $payload['playlist_id']);
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
                $payload,
                3600
            );
        }

        return true;
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
