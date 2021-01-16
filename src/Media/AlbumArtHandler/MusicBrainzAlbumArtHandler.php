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
        $searchQuery = [];

        $searchQuery[] = $this->quoteQuery($song->getTitle());
        if (!empty($song->getArtist())) {
            $searchQuery[] = 'artist:' . $this->quoteQuery($song->getArtist());
        }

        if ($song instanceof Entity\StationMedia) {
            if (!empty($song->getAlbum())) {
                $searchQuery[] = 'release:' . $this->quoteQuery($song->getAlbum());
            }

            if (!empty($song->getIsrc())) {
                $searchQuery[] = 'isrc:' . $this->quoteQuery($song->getIsrc());
            }
        }

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

        $releaseGroupIds = [];
        foreach ($response['recordings'] as $recording) {
            if (empty($recording['releases'])) {
                continue;
            }

            foreach ($recording['releases'] as $release) {
                if (isset($release['release-group']['id'])) {
                    $releaseGroupId = $release['release-group']['id'];

                    if (isset($releaseGroupIds[$releaseGroupId])) {
                        continue; // Already been checked.
                    }
                    $releaseGroupIds[$releaseGroupId] = $releaseGroupId;

                    $groupAlbumArt = $this->musicBrainz->getCoverArt('release-group', $releaseGroupId);

                    if (!empty($groupAlbumArt)) {
                        return $groupAlbumArt;
                    }
                }
            }
        }

        return null;
    }

    protected function quoteQuery(string $query): string
    {
        return '"' . str_replace('"', '\'', $query) . '"';
    }
}
