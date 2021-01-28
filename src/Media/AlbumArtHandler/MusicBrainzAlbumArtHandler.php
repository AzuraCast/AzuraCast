<?php

namespace App\Media\AlbumArtHandler;

use App\Entity;
use App\Service\MusicBrainz;
use Psr\Log\LoggerInterface;

class MusicBrainzAlbumArtHandler extends AbstractAlbumArtHandler
{
    protected MusicBrainz $musicBrainz;

    public function __construct(MusicBrainz $musicBrainz, LoggerInterface $logger)
    {
        parent::__construct($logger);

        $this->musicBrainz = $musicBrainz;
    }

    protected function getServiceName(): string
    {
        return 'MusicBrainz';
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
                'query' => implode(' AND ', $searchQuery),
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
