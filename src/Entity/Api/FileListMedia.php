<?php

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;

class FileListMedia extends Song
{
    use HasLinks;

    public bool $is_playable = false;

    public ?int $length = null;

    public ?string $length_text = null;
}
