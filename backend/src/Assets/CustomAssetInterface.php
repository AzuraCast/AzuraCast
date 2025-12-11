<?php

declare(strict_types=1);

namespace App\Assets;

use App\Entity\Station;
use Intervention\Image\Interfaces\ImageInterface;
use Psr\Http\Message\UriInterface;

interface CustomAssetInterface
{
    public const string UPLOADS_URL_PREFIX = '/uploads';

    public function getPath(?Station $station = null): string;

    public function isUploaded(?Station $station = null): bool;

    public function getUrl(?Station $station = null): string;

    public function getUri(?Station $station = null): UriInterface;

    public function upload(
        ImageInterface $image,
        string $mimeType,
        ?Station $station = null
    ): void;

    public function delete(?Station $station = null): void;
}
