<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Utilities\File;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'Api_UploadFile',
    type: 'object'
)]
final class UploadFile
{
    #[
        OA\Property(
            description: 'The destination path of the uploaded file.',
            example: 'relative/path/to/file.mp3'
        ),
        Assert\NotBlank
    ]
    public string $path;

    #[
        OA\Property(
            description: 'The base64-encoded contents of the file to upload.',
            example: ''
        ),
        Assert\NotBlank
    ]
    public string $file;

    public function getSanitizedFilename(): string
    {
        return File::sanitizeFileName(basename($this->getPath()));
    }

    public function getSanitizedPath(): string
    {
        return File::sanitizePathPrefix($this->getPath());
    }

    public function getPath(): string
    {
        return ltrim($this->path, '/');
    }

    public function getFileContents(): string
    {
        return base64_decode($this->file);
    }
}
