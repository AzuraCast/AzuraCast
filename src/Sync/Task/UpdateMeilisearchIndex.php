<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Message\AddMediaToSearchIndexMessage;
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
        $index = $this->meilisearch->getIndex($storageLocation);
        $index->configure();

        $existingIdsRaw = iterator_to_array($index->getIdsInIndex(), false);
        $existingIds = array_combine($existingIdsRaw, $existingIdsRaw);

        $queuedMedia = [];

        foreach (
            $this->queueManager->getMessagesInTransport(
                QueueManagerInterface::QUEUE_NORMAL_PRIORITY
            ) as $message
        ) {
            if ($message instanceof AddMediaToSearchIndexMessage) {
                foreach ($message->media as $mediaId) {
                    $queuedMedia[$mediaId] = $mediaId;
                }
            }
        }

        $mediaRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT sm.id, sm.unique_id
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->toIterable([], AbstractQuery::HYDRATE_ARRAY);

        $newIds = [];

        foreach ($mediaRaw as $row) {
            if (
                isset($existingIds[$row['unique_id']])
                || isset($queuedMedia[$row['id']])
            ) {
                unset($existingIds[$row['unique_id']]);
                continue;
            }

            $newIds[] = $row['id'];
        }

        foreach (array_chunk($newIds, Meilisearch::BATCH_SIZE) as $batchIds) {
            $message = new AddMediaToSearchIndexMessage();
            $message->storage_location_id = $storageLocation->getIdRequired();
            $message->media = $batchIds;
            $this->messageBus->dispatch($message);
        }

        if (!empty($existingIds)) {
            $index->deleteIds($existingIds);
        }
    }
}
