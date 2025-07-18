<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Stations;

use App\Container\EnvironmentAwareTrait;
use App\Controller\Api\Admin\StationsController;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Interfaces\StationCloneAwareInterface;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationStreamer;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Doctrine\Common\Collections\Collection;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Throwable;

#[
    OA\Post(
        path: '/admin/station/{id}/clone',
        operationId: 'postAdminStationsClone',
        summary: 'Clone a station, preserving certain settings.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'name',
                        description: 'The name of the newly cloned station.',
                        type: 'string'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'The description of the newly cloned station.',
                        type: 'string'
                    ),
                    new OA\Property(
                        property: 'clone',
                        description: 'Which parts of the original station to clone.',
                        type: 'array',
                        items: new OA\Items(
                            type: 'string',
                            enum: [
                                CloneAction::CLONE_MEDIA_STORAGE,
                                CloneAction::CLONE_RECORDINGS_STORAGE,
                                CloneAction::CLONE_PODCASTS_STORAGE,
                                CloneAction::CLONE_PLAYLISTS,
                                CloneAction::CLONE_MOUNTS,
                                CloneAction::CLONE_REMOTES,
                                CloneAction::CLONE_STREAMERS,
                                CloneAction::CLONE_PERMISSIONS,
                                CloneAction::CLONE_WEBHOOKS,
                            ]
                        ),
                    ),
                ]
            )
        ),
        tags: [OpenApi::TAG_ADMIN_STATIONS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Station ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class CloneAction extends StationsController implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public const string CLONE_MEDIA_STORAGE = 'media_storage';
    public const string CLONE_RECORDINGS_STORAGE = 'recordings_storage';
    public const string CLONE_PODCASTS_STORAGE = 'podcasts_storage';

    public const string CLONE_PLAYLISTS = 'playlists';
    public const string CLONE_MOUNTS = 'mounts';
    public const string CLONE_REMOTES = 'remotes';
    public const string CLONE_STREAMERS = 'streamers';
    public const string CLONE_PERMISSIONS = 'permissions';
    public const string CLONE_WEBHOOKS = 'webhooks';

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $record = $this->getRecord($request, $params);
        assert($record instanceof Station);

        $data = (array)$request->getParsedBody();

        $toClone = $data['clone'];

        $this->em->detach($record);

        $newStation = clone $record;
        $newStation->name = $data['name'] ?? ($newStation->name . ' - Copy');
        $newStation->description = $data['description'] ?? $newStation->description;

        // Set new radio base directory
        $stationBaseDir = $this->environment->getStationDirectory();
        $newStation->radio_base_dir = $stationBaseDir . '/' . $newStation->short_name;

        if (in_array(self::CLONE_MEDIA_STORAGE, $toClone, true)) {
            $newStation->media_storage_location = $record->media_storage_location;
        } else {
            $newStation->createMediaStorageLocation();
        }

        if (in_array(self::CLONE_RECORDINGS_STORAGE, $toClone, true)) {
            $newStation->recordings_storage_location = $record->recordings_storage_location;
        } else {
            $newStation->createRecordingsStorageLocation();
        }

        if (in_array(self::CLONE_PODCASTS_STORAGE, $toClone, true)) {
            $newStation->podcasts_storage_location = $record->podcasts_storage_location;
        } else {
            $newStation->createPodcastsStorageLocation();
        }

        $newStation->ensureDirectoriesExist();

        // Persist all newly created records (and relations).
        $this->em->persist($newStation->media_storage_location);
        $this->em->persist($newStation->recordings_storage_location);
        $this->em->persist($newStation->podcasts_storage_location);
        $this->em->persist($newStation);
        $this->em->flush();
        $this->em->clear();

        if (in_array(self::CLONE_PLAYLISTS, $toClone, true)) {
            $afterCloning = function (
                StationPlaylist $oldPlaylist,
                StationPlaylist $newPlaylist,
                Station $newStation
            ) use (
                $toClone
            ): void {
                foreach ($oldPlaylist->schedule_items as $oldScheduleItem) {
                    $this->em->detach($oldScheduleItem);

                    $newScheduleItem = clone $oldScheduleItem;
                    $newScheduleItem->playlist = $newPlaylist;

                    $this->em->persist($newScheduleItem);
                }

                if (in_array(self::CLONE_MEDIA_STORAGE, $toClone, true)) {
                    foreach ($oldPlaylist->folders as $oldPlaylistFolder) {
                        $this->em->detach($oldPlaylistFolder);

                        $newPlaylistFolder = clone $oldPlaylistFolder;
                        $newPlaylistFolder->station = $newStation;
                        $newPlaylistFolder->playlist = $newPlaylist;
                        $this->em->persist($newPlaylistFolder);
                    }

                    foreach ($oldPlaylist->media_items as $oldMediaItem) {
                        $this->em->detach($oldMediaItem);

                        $newMediaItem = clone $oldMediaItem;

                        $newMediaItem->playlist = $newPlaylist;
                        $this->em->persist($newMediaItem);
                    }
                }
            };

            $record = $this->em->refetch($record);
            $this->cloneCollection($record->playlists, $newStation, $afterCloning);
        }

        if (in_array(self::CLONE_MOUNTS, $toClone, true)) {
            $record = $this->em->refetch($record);
            $this->cloneCollection($record->mounts, $newStation);
        } else {
            $newStation = $this->em->refetch($newStation);
            $this->stationRepo->resetMounts($newStation);
        }

        if (in_array(self::CLONE_REMOTES, $toClone, true)) {
            $record = $this->em->refetch($record);
            $this->cloneCollection($record->remotes, $newStation);
        }

        if (in_array(self::CLONE_STREAMERS, $toClone, true)) {
            $record = $this->em->refetch($record);

            $afterCloning = function (
                StationStreamer $oldStreamer,
                StationStreamer $newStreamer,
                Station $station
            ): void {
                foreach ($oldStreamer->schedule_items as $oldScheduleItem) {
                    $this->em->detach($oldScheduleItem);

                    $newScheduleItem = clone $oldScheduleItem;
                    $newScheduleItem->streamer = $newStreamer;

                    $this->em->persist($newScheduleItem);
                }
            };

            $this->cloneCollection($record->streamers, $newStation, $afterCloning);
        }

        if (in_array(self::CLONE_PERMISSIONS, $toClone, true)) {
            $record = $this->em->refetch($record);
            $this->cloneCollection($record->permissions, $newStation);
        }

        if (in_array(self::CLONE_WEBHOOKS, $toClone, true)) {
            $record = $this->em->refetch($record);
            $this->cloneCollection($record->webhooks, $newStation);
        }

        // Clear the EntityManager for later functions.
        $newStation = $this->em->refetch($newStation);

        $this->configuration->assignRadioPorts($newStation, true);

        try {
            $this->configuration->writeConfiguration($newStation);
        } catch (Throwable) {
        }

        $this->em->flush();

        return $response->withJson(Status::created());
    }

    /**
     * @template T of StationCloneAwareInterface
     * @param Collection<int, T> $collection
     */
    private function cloneCollection(
        Collection $collection,
        Station $newStation,
        ?callable $afterCloning = null
    ): void {
        $newStation = $this->em->refetch($newStation);

        foreach ($collection as $oldRecord) {
            $this->em->detach($oldRecord);

            /** @var StationCloneAwareInterface $newRecord */
            $newRecord = clone $oldRecord;
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
