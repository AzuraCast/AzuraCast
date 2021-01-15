<?php

namespace App\Media;

use App\Entity;
use App\Event\Radio\GetAlbumArt;
use App\Service\LastFm;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AlbumArtListener implements EventSubscriberInterface
{
    public const CACHE_LIFETIME = 43200;

    protected LastFm $lastFm;

    protected LoggerInterface $logger;

    protected CacheInterface $cache;

    public function __construct(
        LastFm $lastFm,
        LoggerInterface $logger,
        CacheInterface $cache
    ) {
        $this->lastFm = $lastFm;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GetAlbumArt::class => [
                ['fetchFromCache', 10],
                ['fetchFromLastFm', 0],
            ],
        ];
    }

    public function fetchFromCache(GetAlbumArt $event): void
    {
        $song = $event->getSong();
        $cacheKey = $this->getCacheKey($song);

        if ($this->cache->has($cacheKey)) {
            $cacheResult = $this->cache->get($cacheKey);

            $this->logger->debug(
                'Cached entry found for track.',
                [
                    'result' => $cacheResult,
                    'song' => $song->getText(),
                    'songId' => $song->getSongId(),
                ]
            );

            if ($cacheResult['success']) {
                $event->setAlbumArt($cacheResult['url']);
            } else {
                // Previous attempt to fetch was an exception; stop propagation of this event.
                $event->stopPropagation();
            }
        }
    }

    public function fetchFromLastFm(GetAlbumArt $event): void
    {
        $song = $event->getSong();

        if (!$this->lastFm->hasApiKey()) {
            $this->logger->debug(
                'No Last.fm API key specified; skipping check.',
                [
                    'song' => $song->getText(),
                    'songId' => $song->getSongId(),
                ]
            );
            return;
        }

        try {
            $albumArtUrl = $this->lastFm->getAlbumArt($song);

            if (null !== $albumArtUrl) {
                $this->cache->set(
                    $this->getCacheKey($song),
                    [
                        'success' => true,
                        'url' => $albumArtUrl,
                    ],
                    self::CACHE_LIFETIME
                );

                $event->setAlbumArt($albumArtUrl);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'exception' => $e,
                ]
            );

            $this->cache->set(
                $this->getCacheKey($song),
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                self::CACHE_LIFETIME
            );
        }
    }

    protected function getCacheKey(Entity\SongInterface $song): string
    {
        return 'album_art.' . $song->getSongId();
    }
}
