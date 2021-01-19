<?php

namespace App\Media\AlbumArtHandler;

use App\Entity;
use App\Event\Media\GetAlbumArt;
use App\Service\LastFm;
use Psr\Log\LoggerInterface;

class LastFmAlbumArtHandler
{
    protected LastFm $lastFm;

    protected LoggerInterface $logger;

    public function __construct(LastFm $lastFm, LoggerInterface $logger)
    {
        $this->lastFm = $lastFm;
        $this->logger = $logger;
    }

    public function __invoke(GetAlbumArt $event): void
    {
        if (!$this->lastFm->hasApiKey()) {
            $this->logger->info('No last.fm API key specified; skipping last.fm album art check.');
            return;
        }

        $song = $event->getSong();

        try {
            $albumArt = $this->getAlbumArt($song);
            if (!empty($albumArt)) {
                $event->setAlbumArt($albumArt);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Last.fm Album Art Error: %s', $e->getMessage()),
                [
                    'exception' => $e,
                    'song' => $song->getText(),
                    'songId' => $song->getSongId(),
                ]
            );
        }
    }

    protected function getAlbumArt(Entity\SongInterface $song): ?string
    {
        if ($song instanceof Entity\StationMedia && !empty($song->getAlbum())) {
            $response = $this->lastFm->makeRequest(
                'album.getInfo',
                [
                    'artist' => $song->getArtist(),
                    'album' => $song->getAlbum(),
                ]
            );

            if (isset($response['album'])) {
                return $this->getImageFromArray($response['album']['image'] ?? []);
            }
        }

        $response = $this->lastFm->makeRequest(
            'track.getInfo',
            [
                'artist' => $song->getArtist(),
                'track' => $song->getTitle(),
            ]
        );

        if (isset($response['album'])) {
            return $this->getImageFromArray($response['album']['image'] ?? []);
        }

        return null;
    }

    protected function getImageFromArray(array $images): ?string
    {
        $imagesBySize = [];
        foreach ($images as $image) {
            $size = ('' === $image['size']) ? 'default' : $image['size'];
            $imagesBySize[$size] = $image['#text'];
        }

        return $imagesBySize['large']
            ?? $imagesBySize['extralarge']
            ?? $imagesBySize['default']
            ?? null;
    }
}
