<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface SongInterface
{
    public string $song_id {
        get;
    }

    public ?string $text {
        get;
        set;
    }

    public ?string $artist {
        get;
        set;
    }

    public ?string $title {
        get;
        set;
    }

    public ?string $album {
        get;
        set;
    }

    public function updateMetaFields(): void;
}
