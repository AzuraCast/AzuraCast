<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_FileListDir',
    type: 'object'
)]
final class FileListDir
{
    #[OA\Property(type: "array", items: new OA\Items(
        ref: StationMediaPlaylist::class
    ))]
    public array $playlists = [];
}
