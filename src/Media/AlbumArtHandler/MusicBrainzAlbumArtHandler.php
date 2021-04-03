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
        $releaseGroupIds = [];
        foreach ($this->musicBrainz->findRecordingsForSong($song) as $recording) {
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
}
