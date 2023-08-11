<?php

/** @noinspection SummerTimeUnsafeTimeManipulationInspection */

declare(strict_types=1);

namespace App\Media;

use App\Container\LoggerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Interfaces\SongInterface;
use App\Entity\Song;
use App\Event\Media\GetAlbumArt;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

final class RemoteAlbumArt
{
    use LoggerAwareTrait;
    use SettingsAwareTrait;

    public const CACHE_LIFETIME = 86400 * 14; // Two Weeks

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Client $httpClient
    ) {
    }

    public function enableForApis(): bool
    {
        return $this->readSettings()->getUseExternalAlbumArtInApis();
    }

    public function enableForMedia(): bool
    {
        return $this->readSettings()->getUseExternalAlbumArtWhenProcessingMedia();
    }

    public function getArtwork(SongInterface $media): ?string
    {
        $artUri = $this->getUrlForSong($media);
        if (empty($artUri)) {
            return null;
        }

        // Fetch external artwork.
        $response = $this->httpClient->request(
            'GET',
            $artUri,
            [
                RequestOptions::TIMEOUT => 10,
                RequestOptions::HEADERS => [
                    'User-Agent' => 'AzuraCast ' . Version::FALLBACK_VERSION,
                ],
            ]
        );

        return (string)$response->getBody();
    }

    public function getUrlForSong(SongInterface $song): ?string
    {
        // Avoid tracks that shouldn't ever hit remote APIs.
        if ($song->getSongId() === Song::OFFLINE_SONG_ID) {
            return null;
        }

        // Catch the default error track and derivatives.
        if (false !== mb_stripos($song->getText() ?? '', 'AzuraCast')) {
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
