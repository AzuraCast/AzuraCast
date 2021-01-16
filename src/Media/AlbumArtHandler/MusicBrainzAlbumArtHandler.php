<?php

namespace App\Media\AlbumArtHandler;

use App\Entity;
use App\Event\Media\GetAlbumArt;
use App\Service\MusicBrainz;
use Psr\Log\LoggerInterface;

class MusicBrainzAlbumArtHandler
{
    protected MusicBrainz $musicBrainz;

    protected LoggerInterface $logger;

    public function __construct(MusicBrainz $musicBrainz, LoggerInterface $logger)
    {
        $this->musicBrainz = $musicBrainz;
        $this->logger = $logger;
    }

    public function __invoke(GetAlbumArt $event): void
    {
        $song = $event->getSong();

        try {
            $albumArt = $this->getAlbumArt($song);

            if (!empty($albumArt)) {
                $event->setAlbumArt($albumArt);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('MusicBrainz Album Art Error: %s', $e->getMessage()),
                [
                    'exception' => $e,
                    'song' => $song->getText(),
                    'songId' => $song->getSongId(),
                ]
            );
        }
    }

    public function getAlbumArt(Entity\SongInterface $song): ?string
    {
        $searchQuery = [
            'status:official',
            'primarytype:album',
        ];


        $response = $this->musicBrainz->makeRequest(
            'recording/',
            [
                'query' => implode(' and ', $searchQuery),
                'inc' => 'releases',
                'limit' => 5,
            ]
        );

        if (empty($response['recordings'])) {
            return null;
        }
    }

    protected function quoteQuery(string $query): string
    {
    }
}
