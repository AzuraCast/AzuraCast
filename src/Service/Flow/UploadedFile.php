<?php

declare(strict_types=1);

namespace App\Service\Flow;

use App\Utilities\File;

final class UploadedFile implements \JsonSerializable
{
    protected string $originalFilename;

    protected string $uploadedPath;

    public function __construct(
        ?string $originalFilename,
        ?string $uploadedPath,
        ?string $tempDir
    ) {
        $tempDir ??= sys_get_temp_dir();
        $originalFilename ??= tempnam($tempDir, 'upload');

        if (!$originalFilename || !$tempDir) {
            throw new \RuntimeException('Could not generate original filename.');
        }

        $this->originalFilename = self::filterOriginalFilename($originalFilename);

        if (null === $uploadedPath) {
            $prefix = substr(bin2hex(random_bytes(5)), 0, 9);
            $this->uploadedPath = $tempDir . '/' . $prefix . '_' . $originalFilename;
        } else {
            $uploadedPath = realpath($uploadedPath);
            if (false === $uploadedPath) {
                throw new \InvalidArgumentException('Could not determine real path of specified path.');
            }
            if (!str_starts_with($uploadedPath, $tempDir)) {
                throw new \InvalidArgumentException('Uploaded path is not inside specified temporary directory.');
            }

            if (!is_file($uploadedPath)) {
                throw new \InvalidArgumentException(sprintf('File does not exist at path: %s', $uploadedPath));
            }

            $this->uploadedPath = $uploadedPath;
        }
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getUploadedPath(): string
    {
        return $this->uploadedPath;
    }

    public function getUploadedSize(): int
    {
        $size = filesize($this->uploadedPath);
        if (false === $size) {
            throw new \RuntimeException('Could not get file size of uploaded path.');
        }

        return $size;
    }

    public function readAndDeleteUploadedFile(): string
    {
        $contents = file_get_contents($this->uploadedPath);
        @unlink($this->uploadedPath);

        return $contents ?: '';
    }

    /** @return mixed[] */
    public function jsonSerialize(): array
    {
        return [
            'originalFilename' => $this->originalFilename,
            'uploadedPath' => $this->uploadedPath,
        ];
    }

    public static function fromArray(array $input, string $tempDir): self
    {
        if (!isset($input['originalFilename'], $input['uploadedPath'])) {
            throw new \InvalidArgumentException('Uploaded file array is malformed.');
        }

        return new self($input['originalFilename'], $input['uploadedPath'], $tempDir);
    }

    public static function filterOriginalFilename(string $name): string
    {
        $name = basename($name);
        $normalizedName = \Normalizer::normalize($name, \Normalizer::FORM_KD);
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
