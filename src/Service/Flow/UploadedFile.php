<?php

declare(strict_types=1);

namespace App\Service\Flow;

use App\Utilities\File;
use GuzzleHttp\Psr7\LazyOpenStream;
use InvalidArgumentException;
use JsonSerializable;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use Normalizer;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class UploadedFile implements UploadedFileInterface, JsonSerializable
{
    private string $clientFilename;

    private string $file;

    private bool $moved = false;

    public function __construct(
        ?string $clientFilename,
        ?string $uploadedPath,
        ?string $tempDir
    ) {
        $tempDir ??= sys_get_temp_dir();
        $clientFilename ??= tempnam($tempDir, 'upload');

        if (!$clientFilename || !$tempDir) {
            throw new RuntimeException('Could not generate original filename.');
        }

        $clientFilename = self::filterOriginalFilename($clientFilename);
        $this->clientFilename = $clientFilename;

        if (null === $uploadedPath) {
            $prefix = substr(bin2hex(random_bytes(5)), 0, 9);
            $this->file = $tempDir . '/' . $prefix . '_' . $clientFilename;
        } else {
            $uploadedPath = realpath($uploadedPath);
            if (false === $uploadedPath) {
                throw new InvalidArgumentException('Could not determine real path of specified path.');
            }
            if (!str_starts_with($uploadedPath, $tempDir)) {
                throw new InvalidArgumentException('Uploaded path is not inside specified temporary directory.');
            }

            if (!is_file($uploadedPath)) {
                throw new InvalidArgumentException(sprintf('File does not exist at path: %s', $uploadedPath));
            }

            $this->file = $uploadedPath;
        }
    }

    public function getClientFilename(): string
    {
        return $this->clientFilename;
    }

    public function getUploadedPath(): string
    {
        return $this->file;
    }

    public function getSize(): int
    {
        $this->validateActive();

        $size = filesize($this->file);
        if (false === $size) {
            throw new RuntimeException('Could not get file size of uploaded path.');
        }

        return $size;
    }

    public function readAndDeleteUploadedFile(): string
    {
        $this->validateActive();

        $contents = file_get_contents($this->file);
        $this->delete();

        return $contents ?: '';
    }

    public function delete(): void
    {
        $this->validateActive();

        @unlink($this->file);
        $this->moved = true;
    }

    public function getClientMediaType(): ?string
    {
        $this->validateActive();

        $fileMimeType = (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($this->file);

        if ('application/octet-stream' === $fileMimeType || null === $fileMimeType) {
            $extensionMap = new GeneratedExtensionToMimeTypeMap();
            $extension = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));

            $fileMimeType = $extensionMap->lookupMimeType($extension) ?? 'application/octet-stream';
        }

        return $fileMimeType;
    }

    public function getStream(): StreamInterface
    {
        $this->validateActive();

        return new LazyOpenStream($this->file, 'r+');
    }

    public function moveTo($targetPath): void
    {
        $this->validateActive();

        $this->moved = rename($this->file, $targetPath);

        if (!$this->moved) {
            throw new RuntimeException(
                sprintf('Uploaded file could not be moved to %s', $targetPath)
            );
        }
    }

    public function getError(): int
    {
        return UPLOAD_ERR_OK;
    }

    private function validateActive(): void
    {
        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /** @return mixed[] */
    public function jsonSerialize(): array
    {
        return [
            'originalFilename' => $this->clientFilename,
            'uploadedPath' => $this->file,
        ];
    }

    public static function fromArray(array $input, string $tempDir): self
    {
        if (!isset($input['originalFilename'], $input['uploadedPath'])) {
            throw new InvalidArgumentException('Uploaded file array is malformed.');
        }

        return new self($input['originalFilename'], $input['uploadedPath'], $tempDir);
    }

    public static function filterOriginalFilename(string $name): string
    {
        $name = basename($name);
        $normalizedName = Normalizer::normalize($name, Normalizer::FORM_KD);
        if (false !== $normalizedName) {
            $name = $normalizedName;
        }

        $name = File::sanitizeFileName($name);

        // Truncate filenames whose lengths are longer than 255 characters, while preserving extension.
        $thresholdLength = 255 - 10; // To allow for a prefix.
        if (strlen($name) > $thresholdLength) {
            $fileExt = pathinfo($name, PATHINFO_EXTENSION);
            $fileName = pathinfo($name, PATHINFO_FILENAME);
            $fileName = substr($fileName, 0, $thresholdLength - 1 - strlen($fileExt));
            $name = $fileName . '.' . $fileExt;
        }

        return $name;
    }
}
