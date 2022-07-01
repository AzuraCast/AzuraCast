<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;

final class FileListMedia extends Song
{
    use HasLinks;

    public ?int $media_id = null;

    public ?string $unique_id = null;

    public ?int $art_updated_at = null;

    public bool $is_playable = false;

    public ?int $length = null;

    public ?string $length_text = null;
}
