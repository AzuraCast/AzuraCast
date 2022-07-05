<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Stations;

use App\Controller\Api\Admin\StationsController;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Configuration;
use DeepCopy;
use Doctrine\Common\Collections\Collection;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class CloneAction extends StationsController
{
    public const CLONE_MEDIA_STORAGE = 'media_storage';
    public const CLONE_RECORDINGS_STORAGE = 'recordings_storage';
    public const CLONE_PODCASTS_STORAGE = 'podcasts_storage';

    public const CLONE_PLAYLISTS = 'playlists';
    public const CLONE_MOUNTS = 'mounts';
    public const CLONE_REMOTES = 'remotes';
    public const CLONE_STREAMERS = 'streamers';
    public const CLONE_PERMISSIONS = 'permissions';
    public const CLONE_WEBHOOKS = 'webhooks';

    public function __construct(
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        Entity\Repository\StationQueueRepository $queueRepo,
        Configuration $configuration,
        ReloadableEntityManagerInterface $reloadableEm,
        Serializer $serializer,
        ValidatorInterface $validator,
        private readonly Environment $environment
    ) {
        parent::__construct(
            $stationRepo,
            $storageLocationRepo,
            $queueRepo,
            $configuration,
            $reloadableEm,
            $serializer,
            $validator
        );
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($id);
        $data = (array)$request->getParsedBody();

        $toClone = $data['clone'];

        $copier = new DeepCopy\DeepCopy();
        $copier->addFilter(
            new DeepCopy\Filter\Doctrine\DoctrineProxyFilter(),
            new DeepCopy\Matcher\Doctrine\DoctrineProxyMatcher()
        );
        $copier->addFilter(
            new DeepCopy\Filter\SetNullFilter(),
            new DeepCopy\Matcher\PropertyNameMatcher('id')
        );
        $copier->addFilter(
            new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
            new DeepCopy\Matcher\PropertyTypeMatcher(Collection::class)
        );

        $copier->addFilter(
            new DeepCopy\Filter\KeepFilter(),
            new DeepCopy\Matcher\PropertyMatcher(Entity\RolePermission::class, 'role')
        );
        $copier->addFilter(
            new DeepCopy\Filter\KeepFilter(),
            new DeepCopy\Matcher\PropertyMatcher(Entity\StationPlaylistMedia::class, 'media')
        );

        /** @var Entity\Station $record */
        /** @var Entity\Station $newStation */
        $newStation = $copier->copy($record);

        $newStation->setName($data['name'] ?? ($newStation->getName() . ' - Copy'));
        $newStation->setDescription($data['description'] ?? $newStation->getDescription());

        if (in_array(self::CLONE_MEDIA_STORAGE, $toClone, true)) {
            $newStation->setMediaStorageLocation($record->getMediaStorageLocation());
        }
        if (in_array(self::CLONE_RECORDINGS_STORAGE, $toClone, true)) {
            $newStation->setRecordingsStorageLocation($record->getRecordingsStorageLocation());
        }
        if (in_array(self::CLONE_PODCASTS_STORAGE, $toClone, true)) {
            $newStation->setPodcastsStorageLocation($record->getPodcastsStorageLocation());
        }

        // Set new radio base directory
        $station_base_dir = $this->environment->getStationDirectory();
        $newStation->setRadioBaseDir($station_base_dir . '/' . $newStation->getShortName());

        $newStation->ensureDirectoriesExist();

        // Persist all newly created records (and relations).
        $this->em->persist($newStation->getMediaStorageLocation());
        $this->em->persist($newStation->getRecordingsStorageLocation());
        $this->em->persist($newStation->getPodcastsStorageLocation());
        $this->em->persist($newStation);
        $this->em->flush();
        $this->em->clear();

        if (in_array(self::CLONE_PLAYLISTS, $toClone, true)) {
            $afterCloning = function (
                Entity\StationPlaylist $oldPlaylist,
                Entity\StationPlaylist $newPlaylist,
                Entity\Station $newStation
            ) use (
                $copier,
                $toClone
            ): void {
                foreach ($oldPlaylist->getScheduleItems() as $oldScheduleItem) {
                    /** @var Entity\StationSchedule $newScheduleItem */
                    $newScheduleItem = $copier->copy($oldScheduleItem);
                    $newScheduleItem->setPlaylist($newPlaylist);

                    $this->em->persist($newScheduleItem);
                }

                if (in_array(self::CLONE_MEDIA_STORAGE, $toClone, true)) {
                    foreach ($oldPlaylist->getFolders() as $oldPlaylistFolder) {
                        /** @var Entity\StationPlaylistFolder $newPlaylistFolder */
                        $newPlaylistFolder = $copier->copy($oldPlaylistFolder);
                        $newPlaylistFolder->setStation($newStation);
                        $newPlaylistFolder->setPlaylist($newPlaylist);
                        $this->em->persist($newPlaylistFolder);
                    }

                    foreach ($oldPlaylist->getMediaItems() as $oldMediaItem) {
                        /** @var Entity\StationPlaylistMedia $newMediaItem */
                        $newMediaItem = $copier->copy($oldMediaItem);

                        $newMediaItem->setPlaylist($newPlaylist);
                        $this->em->persist($newMediaItem);
                    }
                }
            };

            $record = $this->reloadableEm->refetch($record);
            $this->cloneCollection($record->getPlaylists(), $newStation, $copier, $afterCloning);
        }

        if (in_array(self::CLONE_MOUNTS, $toClone, true)) {
            $record = $this->reloadableEm->refetch($record);
            $this->cloneCollection($record->getMounts(), $newStation, $copier);
        } else {
            $newStation = $this->reloadableEm->refetch($newStation);
            $this->stationRepo->resetMounts($newStation);
        }

        if (in_array(self::CLONE_REMOTES, $toClone, true)) {
            $record = $this->reloadableEm->refetch($record);
            $this->cloneCollection($record->getRemotes(), $newStation, $copier);
        }

        if (in_array(self::CLONE_STREAMERS, $toClone, true)) {
            $record = $this->reloadableEm->refetch($record);

            $afterCloning = function (
                Entity\StationStreamer $oldStreamer,
                Entity\StationStreamer $newStreamer,
                Entity\Station $station
            ) use (
                $copier
            ): void {
                foreach ($oldStreamer->getScheduleItems() as $oldScheduleItem) {
                    /** @var Entity\StationSchedule $newScheduleItem */
                    $newScheduleItem = $copier->copy($oldScheduleItem);
                    $newScheduleItem->setStreamer($newStreamer);

                    $this->em->persist($newScheduleItem);
                }
            };

            $this->cloneCollection($record->getStreamers(), $newStation, $copier, $afterCloning);
        }

        if (in_array(self::CLONE_PERMISSIONS, $toClone, true)) {
            $record = $this->reloadableEm->refetch($record);
            $this->cloneCollection($record->getPermissions(), $newStation, $copier);
        }

        if (in_array(self::CLONE_WEBHOOKS, $toClone, true)) {
            $record = $this->reloadableEm->refetch($record);
            $this->cloneCollection($record->getWebhooks(), $newStation, $copier);
        }

        // Clear the EntityManager for later functions.
        $newStation = $this->reloadableEm->refetch($newStation);

        $this->configuration->assignRadioPorts($newStation, true);

        try {
            $this->configuration->writeConfiguration($newStation);
        } catch (Throwable) {
        }

        $this->em->flush();

        return $response->withJson(Entity\Api\Status::created());
    }

    /**
     * @param Collection<int, mixed> $collection
     */
    private function cloneCollection(
        Collection $collection,
        Entity\Station $newStation,
        DeepCopy\DeepCopy $copier,
        ?callable $afterCloning = null
    ): void {
        $newStation = $this->reloadableEm->refetch($newStation);

        foreach ($collection as $oldRecord) {
            /** @var Entity\Interfaces\StationCloneAwareInterface $newRecord */
            $newRecord = $copier->copy($oldRecord);
            $newRecord->setStation($newStation);

            $this->em->persist($newRecord);

            if (is_callable($afterCloning)) {
                $afterCloning($oldRecord, $newRecord, $newStation);
            }
        }

        $this->em->flush();
        $this->em->clear();
    }
}
