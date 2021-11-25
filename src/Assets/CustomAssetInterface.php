<?php

declare(strict_types=1);

namespace App\Assets;

use Intervention\Image\Image;
use Psr\Http\Message\UriInterface;

interface CustomAssetInterface
{
    public const UPLOADS_URL_PREFIX = '/uploads';

    public function getPath(): string;

    public function isUploaded(): bool;

    public function getUrl(): string;

    public function getUri(): UriInterface;

    public function upload(Image $image): void;

    public function delete(): void;
}
