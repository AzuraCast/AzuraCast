<?php
namespace App\Entity\Api;

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
    public $path;

    /**
     * @OA\Property(example="")
     *
     * @Assert\NotBlank()
     *
     * @var string The base64-encoded contents of the file to upload.
     */
    public $file;

    public function getSanitizedFilename(): string
    {
        return \Azura\File::sanitizeFileName(basename($this->path));
    }

    public function getSanitizedPath(): string
    {
        return \Azura\File::sanitizePathPrefix($this->path);
    }

    public function getFileContents(): string
    {
        return base64_decode($this->file);
    }
}
