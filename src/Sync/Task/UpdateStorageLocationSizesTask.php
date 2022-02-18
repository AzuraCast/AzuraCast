<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity;
use App\Radio\Quota;
use Azura\DoctrineBatchUtils\ReadWriteBatchIteratorAggregate;
use Brick\Math\BigInteger;
use Exception;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;

class UpdateStorageLocationSizesTask extends AbstractTask
{
    public static function getSchedulePattern(): string
    {
        return '27 * * * *';
    }

    public static function isLongTask(): bool
    {
        return true;
    }

    public function run(bool $force = false): void
    {
        $iterator = ReadWriteBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT sl
                    FROM App\Entity\StorageLocation sl
                DQL
            ),
            1
        );

        foreach ($iterator as $storageLocation) {
            /** @var Entity\StorageLocation $storageLocation */
            $this->updateStorageLocationSize($storageLocation);
        }
    }

    protected function updateStorageLocationSize(Entity\StorageLocation $storageLocation): void
    {
        $fs = $storageLocation->getFilesystem();

        $used = BigInteger::zero();

        try {
            /** @var StorageAttributes $row */
            foreach ($fs->listContents('', true) as $row) {
                if ($row->isFile()) {
                    /** @var FileAttributes $row */
                    $used = $used->plus($row->fileSize() ?? 0);
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('Filesystem error: %s', $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );
        }

        $storageLocation->setStorageUsed($used);
        $this->em->persist($storageLocation);

        $this->logger->info('Storage location size updated.', [
            'storageLocation' => (string)$storageLocation,
            'size'            => Quota::getReadableSize($used),
        ]);
    }
}
