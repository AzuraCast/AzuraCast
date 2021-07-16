<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Utilities\File;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(type="object", schema="Api_UploadFile")
 */
class UploadFile
{
    /**
     * @OA\Property(example="relative/path/to/file.mp3")
     *
     * @Assert\NotBlank()
     *
     * @var string The destination path of the uploaded file.
     */
    public string $path;

    /**
     * @OA\Property(example="")
     *
     * @Assert\NotBlank()
     *
     * @var string The base64-encoded contents of the file to upload.
     */
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
