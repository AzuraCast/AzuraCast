<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use App\Entity\Enums\FileTypes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_FileList',
    type: 'object'
)]
final class FileList
{
    use HasLinks;

    #[OA\Property]
    public string $path;

    #[OA\Property]
    public string $path_short;

    #[OA\Property]
    public string $text = '';

    #[OA\Property]
    public FileTypes $type = FileTypes::Other;

    #[OA\Property]
    public int $timestamp = 0;

    #[OA\Property]
    public ?int $size = null;

    #[OA\Property]
    public ?StationMedia $media = null;

    #[OA\Property]
    public ?FileListDir $dir = null;
}
