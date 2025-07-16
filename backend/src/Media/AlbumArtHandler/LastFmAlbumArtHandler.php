<?php

declare(strict_types=1);

namespace App\Media\AlbumArtHandler;

use App\Entity\Interfaces\SongInterface;
use App\Service\LastFm;

final class LastFmAlbumArtHandler extends AbstractAlbumArtHandler
{
    public function __construct(
        private readonly LastFm $lastFm,
    ) {
    }

    protected function getServiceName(): string
    {
        return 'LastFm';
    }

    protected function isSupported(): bool
    {
        return $this->lastFm->hasApiKey();
    }

    protected function getAlbumArt(SongInterface $song): ?string
    {
        if (!empty($song->album)) {
            $response = $this->lastFm->makeRequest(
                'album.getInfo',
                [
                    'artist' => $song->artist,
                    'album' => $song->album,
                ]
            );

            if (isset($response['album'])) {
                return $this->getImageFromArray($response['album']['image'] ?? []);
            }
        }

        $response = $this->lastFm->makeRequest(
            'track.getInfo',
            [
                'artist' => $song->artist,
                'track' => $song->title,
            ]
        );

        if (isset($response['album'])) {
            return $this->getImageFromArray($response['album']['image'] ?? []);
        }

        return null;
    }

    private function getImageFromArray(array $images): ?string
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
