<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Entity;
use App\Exception\NotFoundException;
use InvalidArgumentException;

abstract class AbstractFileAction
{
    public function __construct(
        protected readonly Entity\Repository\StorageLocationRepository $storageLocationRepo
    ) {
    }

    protected function getFile(string $rawPath): array
    {
        $pathStr = base64_decode($rawPath);
        [$storageLocationId, $path] = explode('|', $pathStr);

        $storageLocation = $this->storageLocationRepo->findByType(
            Entity\Enums\StorageLocationTypes::Backup,
            (int)$storageLocationId
        );

        if (!($storageLocation instanceof Entity\StorageLocation)) {
            throw new InvalidArgumentException('Invalid storage location.');
        }

        $fs = $storageLocation->getFilesystem();

        if (!$fs->fileExists($path)) {
            throw new NotFoundException(__('Backup not found.'));
        }

        return [$path, $fs];
    }
}
