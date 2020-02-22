<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\WritePlaylistFileMessage;
use App\MessageQueue;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Filesystem;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilesController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationMedia::class;
    protected string $resourceRouteName = 'api:stations:file';

    protected Filesystem $filesystem;

    protected Adapters $adapters;

    protected MessageQueue $messageQueue;

    protected Entity\Repository\CustomFieldRepository $custom_fields_repo;

    protected Entity\Repository\SongRepository $song_repo;

    protected Entity\Repository\StationMediaRepository $media_repo;

    protected Entity\Repository\StationPlaylistMediaRepository $playlist_media_repo;

    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Filesystem $filesystem,
        Adapters $adapters,
        MessageQueue $messageQueue,
        Entity\Repository\CustomFieldRepository $custom_fields_repo,
        Entity\Repository\SongRepository $song_repo,
        Entity\Repository\StationMediaRepository $media_repo,
        Entity\Repository\StationPlaylistMediaRepository $playlist_media_repo
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->filesystem = $filesystem;
        $this->adapters = $adapters;
        $this->messageQueue = $messageQueue;

        $this->custom_fields_repo = $custom_fields_repo;
        $this->media_repo = $media_repo;
        $this->song_repo = $song_repo;
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

        $body = $request->getParsedBody();

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

        $sanitized_path = 'media://' . $api_record->getSanitizedPath();

        // Process temp path as regular media record.
        $record = $this->media_repo->uploadFile($station, $temp_path, $sanitized_path);

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
                        $path_full = 'media://' . $new_value;

                        $fs = $this->filesystem->getForStation($record->getStation());
                        $fs->rename($record->getPathUri(), $path_full);
                    }

                    return $new_value;
                },
            ],
        ]));

        if ($record instanceof Entity\StationMedia) {
            $this->em->persist($record);
            $this->em->flush($record);

            if ($this->media_repo->writeToFile($record)) {
                $song_info = [
                    'title' => $record->getTitle(),
                    'artist' => $record->getArtist(),
                ];

                $song = $this->song_repo->getOrCreate($song_info);
                $song->update($song_info);
                $this->em->persist($song);

                $record->setSong($song);
            }

            if (null !== $custom_fields) {
                $this->custom_fields_repo->setCustomFields($record, $custom_fields);
            }

            if (null !== $playlists) {
                $station = $record->getStation();

                /** @var Entity\StationPlaylist[] $playlists */
                $affected_playlists = [];

                // Remove existing playlists.
                $media_playlists = $this->playlist_media_repo->clearPlaylistsFromMedia($record);
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
                        $playlist_id = $new_playlist;
                        $playlist_weight = 0;
                    }

                    $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                        'station_id' => $station->getId(),
                        'id' => (int)$playlist_id,
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
                        $this->messageQueue->produce($message);
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

        /** @var Entity\StationPlaylist[] $playlists */
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
                $this->messageQueue->produce($message);
            }
        }

        parent::deleteRecord($record);
    }
}
