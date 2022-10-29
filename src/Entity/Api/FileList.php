<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;

final class FileList
{
    use HasLinks;

    public string $path;

    public string $path_short;

    public string $text = '';

    public int $timestamp = 0;

    public ?int $size = null;

    public bool $is_dir = false;

    public bool $is_cover_art = false;

    public FileListMedia $media;

    public array $playlists = [];
}
