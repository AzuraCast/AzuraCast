<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity\PodcastMedia;
use App\Entity\Repository\StorageLocationRepository;
use League\Flysystem\UnableToRetrieveMetadata;

final class CheckPodcastMediaTask extends AbstractTask
{
    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '*/10 * * * *';
    }

    public function run(bool $force = false): void
    {
        $iterator = ReadWriteBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT pm
                    FROM App\Entity\PodcastMedia pm
                    WHERE pm.size = 0
                DQL
            ),
            50
        );

        $stats = [
            'updated' => 0,
            'not_found' => 0,
        ];

        foreach ($iterator as $podcastMedia) {
            /** @var PodcastMedia $podcastMedia */
            $fs = $this->storageLocationRepo->getAdapter($podcastMedia->storage_location)
                ->getFilesystem();

            try {
                $podcastMedia->size = $fs->fileSize($podcastMedia->path);
            } catch (UnableToRetrieveMetadata) {
                $stats['not_found']++;
                continue;
            }

            $this->em->persist($podcastMedia);
            $stats['updated']++;
        }

        $this->logger->debug('Podcast media file sizes checked.', $stats);
    }
}
