<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Message\Meilisearch\AddMediaMessage;
use App\MessageQueue\QueueManagerInterface;
use App\Service\Meilisearch;
use Doctrine\ORM\AbstractQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;

final class UpdateMeilisearchIndex extends AbstractTask
{
    public function __construct(
        private readonly MessageBus $messageBus,
        private readonly QueueManagerInterface $queueManager,
        private readonly Meilisearch $meilisearch,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return '3-59/5 * * * *';
    }

    public static function isLongTask(): bool
    {
        return true;
    }

    public function run(bool $force = false): void
    {
        if (!$this->meilisearch->isSupported()) {
            $this->logger->debug('Meilisearch is not supported on this instance. Skipping sync task.');
        }

        $storageLocations = $this->iterateStorageLocations(Entity\Enums\StorageLocationTypes::StationMedia);

        foreach ($storageLocations as $storageLocation) {
            $this->logger->info(
                sprintf(
                    'Updating MeiliSearch index for storage location %s...',
                    $storageLocation
                )
            );

            $this->updateIndex($storageLocation);
        }
    }

    public function updateIndex(Entity\StorageLocation $storageLocation): void
    {
        $stats = [
            'existing' => 0,
            'queued' => 0,
            'added' => 0,
            'updated' => 0,
            'deleted' => 0,
        ];

        $index = $this->meilisearch->getIndex($storageLocation);
        $index->configure();

        $existingIds = $index->getIdsInIndex();
        $stats['existing'] = count($existingIds);

        $queuedMedia = [];

        foreach (
            $this->queueManager->getMessagesInTransport(
                QueueManagerInterface::QUEUE_NORMAL_PRIORITY
            ) as $message
        ) {
            if ($message instanceof AddMediaMessage) {
                foreach ($message->media_ids as $mediaId) {
                    $queuedMedia[$mediaId] = $mediaId;
                    $stats['queued']++;
                }
            }
        }

        $mediaRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT sm.id, sm.mtime
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->toIterable([], AbstractQuery::HYDRATE_ARRAY);

        $newIds = [];
        $idsToUpdate = [];

        foreach ($mediaRaw as $row) {
            $mediaId = $row['id'];

            if (isset($queuedMedia[$mediaId])) {
                unset($existingIds[$mediaId]);
                continue;
            }

            if (isset($existingIds[$mediaId])) {
                if ($existingIds[$mediaId] < $row['mtime']) {
                    $idsToUpdate[] = $mediaId;
                    $stats['updated']++;
                }

                unset($existingIds[$mediaId]);
                continue;
            }

            $newIds[] = $mediaId;
            $stats['added']++;
        }

        foreach (array_chunk($idsToUpdate, Meilisearch::BATCH_SIZE) as $batchIds) {
            $message = new AddMediaMessage();
            $message->storage_location_id = $storageLocation->getIdRequired();
            $message->media_ids = $batchIds;
            $message->include_playlists = true;

            $this->messageBus->dispatch($message);
        }

        foreach (array_chunk($newIds, Meilisearch::BATCH_SIZE) as $batchIds) {
            $message = new AddMediaMessage();
            $message->storage_location_id = $storageLocation->getIdRequired();
            $message->media_ids = $batchIds;
            $message->include_playlists = true;

            $this->messageBus->dispatch($message);
        }

        if (!empty($existingIds)) {
            $stats['deleted'] = count($existingIds);
            $index->deleteIds($existingIds);
        }

        $this->logger->debug(sprintf('Meilisearch processed for "%s".', $storageLocation), $stats);
    }
}
