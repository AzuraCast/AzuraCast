<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration;

use App\Entity\StationMedia;
use App\Entity\StationPlaylist;

final class ImportSummary
{
    /** @var array<string, StationPlaylist> playlist ref => created playlist */
    public array $playlistsByRef = [];

    /** @var array<string, StationMedia> media ref => resolved/generated media */
    public array $mediaByRef = [];

    public int $playlistsCreated {
        get => count($this->playlistsByRef);
    }

    public int $foldersCreated = 0;
    public int $schedulesCreated = 0;
    public int $mediaItemsCreated = 0;
    public int $mediaRelinked = 0;
    public int $mediaGenerated = 0;
    public int $membersCreated = 0;

    /** @var string[] */
    public array $warnings = [];
}
