<?php

declare(strict_types=1);

namespace App\Media\AlbumArtHandler;

use App\Entity\Interfaces\SongInterface;
use App\Entity\StationMedia;
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

    protected function getAlbumArt(SongInterface $song): ?string
    {
        if ($song instanceof StationMedia && !empty($song->getAlbum())) {
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
