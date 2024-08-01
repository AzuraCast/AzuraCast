<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Controller\SingleActionInterface;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\StorageLocation;
use App\Exception\NotFoundException;
use InvalidArgumentException;

abstract class AbstractFileAction implements SingleActionInterface
{
    public function __construct(
        protected readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    protected function getFile(string $rawPath): array
    {
        $pathStr = base64_decode($rawPath);
        [$storageLocationId, $path] = explode('|', $pathStr);

        $storageLocation = $this->storageLocationRepo->findByType(
            StorageLocationTypes::Backup,
            (int)$storageLocationId
        );

        if (!($storageLocation instanceof StorageLocation)) {
            throw new InvalidArgumentException('Invalid storage location.');
        }

        $fs = $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        if (!$fs->fileExists($path)) {
            throw NotFoundException::file();
        }

        return [$path, $fs];
    }
}
