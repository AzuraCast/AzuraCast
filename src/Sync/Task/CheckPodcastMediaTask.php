<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\BatchIteratorAggregate;
use App\Entity\Repository\StationPodcastMediaRepository;
use App\Entity\Station;
use App\Entity\StationPodcast;
use App\Entity\StationPodcastEpisode;
use App\Entity\StationPodcastMedia;
use App\Entity\StorageLocation;
use App\Message;
use App\Message\AddNewPodcastMediaMessage;
use App\Message\ReprocessPodcastMediaMessage;
use App\MessageQueue\QueueManager;
use App\Sync\PodcastMediaSyncStatistics;
use App\Sync\QueuedPodcastMediaMessages;
use App\Sync\Task\AbstractTask;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;
use TypeError;

class CheckPodcastMediaTask extends AbstractTask
{
    protected StationPodcastMediaRepository $podcastMediaRepository;

    protected MessageBus $messageBus;

    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        StationPodcastMediaRepository $podcastMediaRepository,
        MessageBus $messageBus,
        QueueManager $queueManager
    ) {
        parent::__construct($em, $logger);

        $this->podcastMediaRepository = $podcastMediaRepository;
        $this->messageBus = $messageBus;
        $this->queueManager = $queueManager;
    }

    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof ReprocessPodcastMediaMessage) {
            $this->logger->debug(sprintf(
                'Processing message of type "%s" with Podcast Media ID "%s"',
                ReprocessPodcastMediaMessage::class,
                $message->podcastMediaId
            ));

            $podcastMedia = $this->em->find(StationPodcastMedia::class, $message->podcastMediaId);

            if ($podcastMedia instanceof StationPodcastMedia) {
                $this->podcastMediaRepository->processPodcastMedia($podcastMedia, $message->force);
                $this->em->flush();
            }
        } elseif ($message instanceof AddNewPodcastMediaMessage) {
            $this->logger->debug(sprintf(
                'Processing message of type "%s" with Storage Location ID "%s" and Path "%s"',
                AddNewPodcastMediaMessage::class,
                $message->storageLocationId,
                $message->path
            ));

            /** @var Station $station */
            foreach ($this->fetchStationsForStorageLocation($message->storageLocationId) as $station) {
                $this->podcastMediaRepository->getOrCreate($station, $message->path);
            }
        }
    }

    public function run(bool $force = false): void
    {
        $storageLocations = $this->iterateStorageLocations(StorageLocation::TYPE_STATION_PODCASTS);

        /** @var StorageLocation $storageLocation */
        foreach ($storageLocations as $storageLocation) {
            $this->logger->info(
                sprintf(
                    'Processing podcast media for storage location "%s" ...',
                    (string) $storageLocation
                )
            );

            try {
                $this->importPodcastMedia($storageLocation);
            } catch (FilesystemException $exception) {
                $this->logger->error(
                    sprintf('Flysystem Error for Storage Space %s', (string) $storageLocation),
                    [
                        'exception' => $exception,
                    ]
                );
                continue;
            } finally {
                gc_collect_cycles();
            }
        }
    }

    public function importPodcastMedia(StorageLocation $storageLocation): void
    {
        $syncStatistics = new PodcastMediaSyncStatistics();

        $podcastFiles = $this->aggregateFilesToProcess($syncStatistics, $storageLocation);

        $queuedPodcastMediaMessages = $this->fetchAlreadyQueuedPodcastMediaMessages(
            $storageLocation
        );

        /** @var Station $station */
        foreach ($this->fetchStationsForStorageLocation($storageLocation->getId()) as $station) {
            $podcastFiles = $this->processExistingPodcastMediaRows(
                $station,
                $syncStatistics,
                $queuedPodcastMediaMessages,
                $podcastFiles
            );
        }

        $this->createNewPodcastMedia(
            $podcastFiles,
            $storageLocation,
            $queuedPodcastMediaMessages,
            $syncStatistics
        );

        $this->logger->debug(
            sprintf(
                'Podcast Media processed for storage location "%s".',
                (string) $storageLocation
            ),
            $syncStatistics->jsonSerialize()
        );
    }

    protected function fetchStationsForStorageLocation(int $storageLocationId): BatchIteratorAggregate
    {
        $storageLocation = $this->em->find(StorageLocation::class, $storageLocationId);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('s')
            ->from(Station::class, 's')
            ->where('s.podcasts_storage_location = :storageLocation')
            ->setParameter('storageLocation', $storageLocation);

        return BatchIteratorAggregate::fromQuery($queryBuilder->getQuery(), 1);
    }

    /**
     * @return StorageAttributes[]
     */
    protected function aggregateFilesToProcess(
        PodcastMediaSyncStatistics $syncStatistics,
        StorageLocation $storageLocation
    ): array {
        $podcastsFilesystem = $storageLocation->getFilesystem();

        $podcastFiles = [];

        $podcastFilesystemIterator = $podcastsFilesystem->listContents('/', true)->filter(
            function (StorageAttributes $attrs) {
                return (
                    $attrs->isFile()
                    && !str_starts_with($attrs->path(), StationPodcast::DIR_PODCAST_ARTWORK)
                    && !str_starts_with($attrs->path(), StationPodcastEpisode::DIR_PODCAST_EPISODE_ARTWORK)
                    && !str_starts_with($attrs->path(), StationPodcastMedia::DIR_PODCAST_MEDIA_ARTWORK)
                );
            }
        );

        /** @var StorageAttributes $file */
        foreach ($podcastFilesystemIterator as $file) {
            $size = $podcastsFilesystem->fileSize($file->path());
            $syncStatistics->totalSize = $syncStatistics->totalSize->plus($size);

            $pathHash = md5($file->path());
            $podcastFiles[$pathHash] = $file;
        }

        $storageLocation->setStorageUsed($syncStatistics->totalSize);
        $this->em->persist($storageLocation);
        $this->em->flush();

        $syncStatistics->totalFiles = count($podcastFiles);

        return $podcastFiles;
    }

    protected function fetchAlreadyQueuedPodcastMediaMessages(
        StorageLocation $storageLocation
    ): QueuedPodcastMediaMessages {
        $queuedPodcastMediaMessages = new QueuedPodcastMediaMessages();

        foreach ($this->queueManager->getMessagesInTransport(QueueManager::QUEUE_PODCAST_MEDIA) as $message) {
            if ($message instanceof Message\ReprocessPodcastMediaMessage) {
                $queuedPodcastMediaMessages->addQueuedUpdatePodcastMedia($message->podcastMediaId);
            } elseif (
                $message instanceof Message\AddNewPodcastMediaMessage
                && $message->storageLocationId === $storageLocation->getId()
            ) {
                $queuedPodcastMediaMessages->addQueuedNewPodcastMediaFile($message->path);
            }
        }

        return $queuedPodcastMediaMessages;
    }

    /**
     * @return StorageAttributes[]
     */
    protected function processExistingPodcastMediaRows(
        Station $station,
        PodcastMediaSyncStatistics $syncStatistics,
        QueuedPodcastMediaMessages $queuedPodcastMediaMessages,
        array $podcastFiles
    ): array {
        $existingPodcastMediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT spm.id, spm.path, spm.modifiedTime, spm.unique_id
                FROM App\Entity\StationPodcastMedia spm
                WHERE spm.station = :station
            DQL
        )->setParameter('station', $station);

        $podcastMediaRecords = $existingPodcastMediaQuery->toIterable([], Query::HYDRATE_ARRAY);

        foreach ($podcastMediaRecords as $podcastMediaRow) {
            $path = $podcastMediaRow['path'];
            $pathHash = md5($path);

            if (!isset($podcastFiles[$pathHash])) {
                $podcastMedia = $this->em->find(StationPodcastMedia::class, $podcastMediaRow['id']);
                $this->removeDeletedPodcastMedia($podcastMedia, $syncStatistics);

                continue;
            }

            /** @var StorageAttributes $file */
            $file = $podcastFiles[$pathHash];

            if ($queuedPodcastMediaMessages->isPodcastMediaUpdateQueued($podcastMediaRow['id'])) {
                $syncStatistics->alreadyQueued++;
            } elseif (
                empty($podcastMediaRow['unique_id'])
                || $file->lastModified() > $podcastMediaRow['modifiedTime']
            ) {
                $message = new ReprocessPodcastMediaMessage();
                $message->podcastMediaId = $podcastMediaRow['id'];
                $message->force = empty($podcastMediaRow['unique_id']);

                $this->messageBus->dispatch($message);

                $syncStatistics->updated++;
            } else {
                $syncStatistics->unchanged++;
            }

            unset($podcastFiles[$pathHash]);
        }

        return $podcastFiles;
    }

    protected function createNewPodcastMedia(
        array $podcastFiles,
        StorageLocation $storageLocation,
        QueuedPodcastMediaMessages $queuedPodcastMediaMessages,
        PodcastMediaSyncStatistics $syncStatistics
    ): void {
        /** @var StorageAttributes $newPodcastMediaFile */
        foreach ($podcastFiles as $newPodcastMediaFile) {
            $path = $newPodcastMediaFile->path();

            if ($queuedPodcastMediaMessages->isNewPodcastMediaFileQueued($path)) {
                $syncStatistics->alreadyQueued++;

                continue;
            }

            $message = new AddNewPodcastMediaMessage();
            $message->storageLocationId = $storageLocation->getId();
            $message->path = $path;

            $this->messageBus->dispatch($message);

            $syncStatistics->created++;
        }
    }

    protected function removeDeletedPodcastMedia(
        StationPodcastMedia $podcastMedia,
        PodcastMediaSyncStatistics $syncStatistics
    ): void {
        $this->podcastMediaRepository->removePodcastArtwork($podcastMedia);
        $this->em->remove($podcastMedia);

        $syncStatistics->deleted++;
    }
}
