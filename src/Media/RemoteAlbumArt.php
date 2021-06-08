<?php

/** @noinspection SummerTimeUnsafeTimeManipulationInspection */

namespace App\Media;

use App\Entity;
use App\Event\Media\GetAlbumArt;
use App\EventDispatcher;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

class RemoteAlbumArt
{
    public const CACHE_LIFETIME = 86400 * 14; // Two Weeks

    public function __construct(
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected EventDispatcher $eventDispatcher
    ) {
    }

    public function enableForApis(): bool
    {
        return $this->settingsRepo->readSettings()->getUseExternalAlbumArtInApis();
    }

    public function enableForMedia(): bool
    {
        return $this->settingsRepo->readSettings()->getUseExternalAlbumArtWhenProcessingMedia();
    }

    public function __invoke(Entity\Interfaces\SongInterface $song): ?string
    {
        // Avoid tracks that shouldn't ever hit remote APIs.
        $offlineSong = Entity\Song::createOffline();
        if ($song->getSongId() === $offlineSong->getSongId()) {
            return null;
        }

        // Catch the default error track and derivatives.
        if (false !== mb_stripos($song->getText(), 'AzuraCast')) {
            return null;
        }

        // Check for cached API hits for the same song ID before.
        $cacheKey = 'album_art.' . $song->getSongId();

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
                return $cacheResult['url'];
            }

            return null;
        }

        // Dispatch new event to various registered handlers.
        try {
            $event = new GetAlbumArt($song);
            $this->eventDispatcher->dispatch($event);

            $albumArtUrl = $event->getAlbumArt();

            if (null !== $albumArtUrl) {
                $this->cache->set(
                    $cacheKey,
                    [
                        'success' => true,
                        'url' => $albumArtUrl,
                    ],
                    self::CACHE_LIFETIME
                );
            }
        } catch (Throwable $e) {
            $albumArtUrl = null;

            $this->cache->set(
                $cacheKey,
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            );
        }

        return $albumArtUrl;
    }
}
