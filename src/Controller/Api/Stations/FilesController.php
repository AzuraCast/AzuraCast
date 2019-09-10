<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\Validation;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Filesystem;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilesController extends AbstractStationApiCrudController
{
    protected $entityClass = Entity\StationMedia::class;
    protected $resourceRouteName = 'api:stations:file';

    /** @var Filesystem */
    protected $filesystem;

    /** @var Adapters */
    protected $adapters;

    /** @var Entity\Repository\StationMediaRepository */
    protected $media_repo;

    /** @var EntityRepository */
    protected $playlist_repo;

    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $playlist_media_repo;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param Filesystem $filesystem
     * @param Adapters $adapters
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Filesystem $filesystem,
        Adapters $adapters
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->filesystem = $filesystem;
        $this->adapters = $adapters;

        $this->media_repo = $em->getRepository(Entity\StationMedia::class);
        $this->playlist_repo = $em->getRepository(Entity\StationPlaylist::class);
        $this->playlist_media_repo = $em->getRepository(Entity\StationPlaylistMedia::class);
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
     * @return ResponseInterface
     */
    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->_getStation($request);

        $body = $request->getParsedBody();

        // Convert the body into an UploadFile API entity first.
        /** @var Entity\Api\UploadFile $api_record */
        $api_record = $this->serializer->denormalize($request->getParsedBody(), Entity\Api\UploadFile::class, null, []);

        // Validate the UploadFile API record.
        $errors = $this->validator->validate($api_record);
        if (count($errors) > 0) {
            $e = new Validation((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        // Write file to temp path.
        $temp_path = $station->getRadioTempDir() . '/' . $api_record->getSanitizedFilename();
        file_put_contents($temp_path, $api_record->getFileContents());

        $sanitized_path = 'media://' . $api_record->getSanitizedPath();

        // Process temp path as regular media record.
        $record = $this->media_repo->uploadFile($station, $temp_path, $sanitized_path);

        $router = $request->getRouter();
        $return = $this->_viewRecord($record, $router);

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
    protected function _normalizeRecord($record, array $context = [])
    {
        $row = parent::_normalizeRecord($record, $context);

        if ($record instanceof Entity\StationMedia) {
            $row['custom_fields'] = $this->media_repo->getCustomFields($record);
        }

        return $row;
    }

    /**
     * @inheritDoc
     */
    protected function _denormalizeToRecord($data, $record = null, array $context = []): object
    {
        $custom_fields = $data['custom_fields'] ?? null;
        $playlists = $data['playlists'] ?? null;
        unset($data['custom_fields'], $data['playlists']);

        $record = parent::_denormalizeToRecord($data, $record, array_merge($context, [
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
            if (null !== $custom_fields) {
                $this->media_repo->setCustomFields($record, $custom_fields);
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

                    $playlist = $this->playlist_repo->findOneBy([
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
                    foreach ($affected_playlists as $playlist) {
                        /** @var Entity\StationPlaylist $playlist */
                        $backend->writePlaylistFile($playlist);
                    }
                }
            }
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function _deleteRecord($record): void
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

        // Write new PLS playlist configuration.
        $backend = $this->adapters->getBackendAdapter($station);
        if ($backend instanceof Liquidsoap) {
            foreach ($affected_playlists as $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                $backend->writePlaylistFile($playlist);
            }
        }

        parent::_deleteRecord($record);
    }
}
