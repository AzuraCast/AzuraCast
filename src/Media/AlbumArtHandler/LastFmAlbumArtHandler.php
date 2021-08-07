<?php

declare(strict_types=1);

namespace App\Media\AlbumArtHandler;

use App\Entity;
use App\Service\LastFm;
use Psr\Log\LoggerInterface;

class LastFmAlbumArtHandler extends AbstractAlbumArtHandler
{
    public function __construct(
        protected LastFm $lastFm,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    protected function getServiceName(): string
    {
        return 'LastFm';
    }

    protected function getAlbumArt(Entity\Interfaces\SongInterface $song): ?string
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
