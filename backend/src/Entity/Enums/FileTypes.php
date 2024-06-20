<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum FileTypes: string
{
    case Directory = 'directory';
    case Media = 'media';
    case CoverArt = 'cover_art';
    case UnprocessableFile = 'unprocessable_file';
    case Other = 'other';
}
