<?php

namespace App\Media\AlbumArtService;

use App\Entity;
use App\Service\LastFm;

class LastFmAlbumArtService implements AlbumArtServiceInterface
{
    protected LastFm $lastFm;

    public function __construct(LastFm $lastFm)
    {
        $this->lastFm = $lastFm;
    }

    public function isSupported(): bool
    {
        return $this->lastFm->hasApiKey();
    }

    public function getAlbumArt(Entity\SongInterface $song): ?string
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
