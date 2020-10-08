<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\ValidationException;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\WritePlaylistFileMessage;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilesController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationMedia::class;
    protected string $resourceRouteName = 'api:stations:file';

    protected Filesystem $filesystem;

    protected Adapters $adapters;

    protected MessageBus $messageBus;

    protected Entity\Repository\CustomFieldRepository $custom_fields_repo;

    protected Entity\Repository\StationMediaRepository $media_repo;

    protected Entity\Repository\StationPlaylistMediaRepository $playlist_media_repo;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Filesystem $filesystem,
        Adapters $adapters,
        MessageBus $messageBus,
        Entity\Repository\CustomFieldRepository $custom_fields_repo,
        Entity\Repository\StationMediaRepository $media_repo,
        Entity\Repository\StationPlaylistMediaRepository $playlist_media_repo
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->filesystem = $filesystem;
        $this->adapters = $adapters;
        $this->messageBus = $messageBus;

        $this->custom_fields_repo = $custom_fields_repo;
        $this->media_repo = $media_repo;
        $this->playlist_media_repo = $playlist_media_repo;
    }

    /**
     * @OA\Get(path="/station/{station_id}/files",
     *   tags={"Stations: Media"},
     *   description="List all current uploaded files.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StationMedia"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    /**
     * @OA\Post(path="/station/{station_id}/files",
     *   tags={"Stations: Media"},
     *   description="Upload a new file.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Api_UploadFile")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationMedia")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->getStation($request);

        if ($station->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('This station is out of available storage space.')));
        }

        $request->getParsedBody();

        // Convert the body into an UploadFile API entity first.
        /** @var Entity\Api\UploadFile $api_record */
        $api_record = $this->serializer->denormalize($request->getParsedBody(), Entity\Api\UploadFile::class, null, []);

        // Validate the UploadFile API record.
        $errors = $this->validator->validate($api_record);
        if (count($errors) > 0) {
            $e = new ValidationException((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        // Write file to temp path.
        $temp_path = $station->getRadioTempDir() . '/' . $api_record->getSanitizedFilename();
        file_put_contents($temp_path, $api_record->getFileContents());

        $sanitized_path = Filesystem::PREFIX_MEDIA . '://' . $api_record->getSanitizedPath();

        // Process temp path as regular media record.
        $record = $this->media_repo->getOrCreate($station, $sanitized_path, $temp_path);

        $return = $this->viewRecord($record, $request);

        return $response->withJson($return);
    }

    /**
     * @OA\Get(path="/station/{station_id}/file/{id}",
     *   tags={"Stations: Media"},
     *   description="Retrieve details for a single file.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Media ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationMedia")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/file/{id}",
     *   tags={"Stations: Media"},
     *   description="Update details of a single file.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationMedia")
     *   ),
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Media ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Delete(path="/station/{station_id}/file/{id}",
     *   tags={"Stations: Media"},
     *   description="Delete a single file.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Media ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    /**
     * @inheritDoc
     */
    protected function getRecord(Entity\Station $station, $id)
    {
        $repo = $this->em->getRepository($this->entityClass);

        $fieldsToCheck = ['id', 'unique_id', 'song_id'];

        foreach ($fieldsToCheck as $field) {
            $record = $repo->findOneBy([
                'station' => $station,
                $field => $id,
            ]);

            if ($record instanceof $this->entityClass) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function toArray($record, array $context = [])
    {
        $row = parent::toArray($record, $context);

        if ($record instanceof Entity\StationMedia) {
            $row['custom_fields'] = $this->custom_fields_repo->getCustomFields($record);
        }

        return $row;
    }

    /**
     * @inheritDoc
     */
    protected function fromArray($data, $record = null, array $context = []): object
    {
        $custom_fields = $data['custom_fields'] ?? null;
        $playlists = $data['playlists'] ?? null;
        unset($data['custom_fields'], $data['playlists']);

        $record = parent::fromArray($data, $record, array_merge($context, [
            AbstractNormalizer::CALLBACKS => [
                'path' => function ($new_value, $record) {
                    // Detect and handle a rename.
                    if (($record instanceof Entity\StationMedia) && $new_value !== $record->getPath()) {
                        $path_full = Filesystem::PREFIX_MEDIA . '://' . $new_value;

                        $fs = $this->filesystem->getForStation($record->getStation());
                        $fs->rename($record->getPathUri(), $path_full);
                    }

                    return $new_value;
                },
            ],
        ]));

        if ($record instanceof Entity\StationMedia) {
            $this->em->persist($record);
            $this->em->flush();

            if ($this->media_repo->writeToFile($record)) {
                $record->updateSongId();
            }

            if (null !== $custom_fields) {
                $this->custom_fields_repo->setCustomFields($record, $custom_fields);
            }

            if (null !== $playlists) {
                $station = $record->getStation();

                /** @var Entity\StationPlaylist[] $affected_playlists */
                $affected_playlists = [];

                // Remove existing playlists.
                $media_playlists = $this->playlist_media_repo->clearPlaylistsFromMedia($record);
                $this->em->flush();

                foreach ($media_playlists as $playlist_id => $playlist) {
                    if (!isset($affected_playlists[$playlist_id])) {
                        $affected_playlists[$playlist_id] = $playlist;
                    }
                }

                // Set new playlists.
                foreach ($playlists as $new_playlist) {
                    if (is_array($new_playlist)) {
                        $playlist_id = $new_playlist['id'];
                        $playlist_weight = $new_playlist['weight'] ?? 0;
                    } else {
                        $playlist_id = (int)$new_playlist;
                        $playlist_weight = 0;
                    }

                    $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                        'station_id' => $station->getId(),
                        'id' => $playlist_id,
                    ]);

                    if ($playlist instanceof Entity\StationPlaylist) {
                        $affected_playlists[$playlist->getId()] = $playlist;
                        $this->playlist_media_repo->addMediaToPlaylist($record, $playlist, $playlist_weight);
                    }
                }

                // Handle playlist changes.
                $backend = $this->adapters->getBackendAdapter($station);
                if ($backend instanceof Liquidsoap) {
                    foreach ($affected_playlists as $playlist_id => $playlist_row) {
                        // Instruct the message queue to start a new "write playlist to file" task.
                        $message = new WritePlaylistFileMessage;
                        $message->playlist_id = $playlist_id;

                        $this->messageBus->dispatch($message);
                    }
                }
            }
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function deleteRecord($record): void
    {
        if (!($record instanceof Entity\StationMedia)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $station = $record->getStation();

        /** @var Entity\StationPlaylist[] $affected_playlists */
        $affected_playlists = [];

        $media_playlists = $this->playlist_media_repo->clearPlaylistsFromMedia($record);
        foreach ($media_playlists as $playlist_id => $playlist) {
            if (!isset($affected_playlists[$playlist_id])) {
                $affected_playlists[$playlist_id] = $playlist;
            }
        }

        // Delete the media file off the filesystem.
        $fs = $this->filesystem->getForStation($station);

        $fs->delete($record->getPathUri());
        $fs->delete($record->getArtPath());

        // Write new PLS playlist configuration.
        $backend = $this->adapters->getBackendAdapter($station);
        if ($backend instanceof Liquidsoap) {
            foreach ($affected_playlists as $playlist_id => $playlist_row) {
                // Instruct the message queue to start a new "write playlist to file" task.
                $message = new WritePlaylistFileMessage;
                $message->playlist_id = $playlist_id;

                $this->messageBus->dispatch($message);
            }
        }

        parent::deleteRecord($record);
    }
}
