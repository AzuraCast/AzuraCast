<?php

declare(strict_types=1);

namespace App\Assets;

use Intervention\Image\Interfaces\ImageInterface;
use Psr\Http\Message\UriInterface;

interface CustomAssetInterface
{
    public const UPLOADS_URL_PREFIX = '/uploads';

    public function getPath(): string;

    public function isUploaded(): bool;

    public function getUrl(): string;

    public function getUri(): UriInterface;

    public function upload(ImageInterface $image, string $mimeType): void;

    public function delete(): void;
}
