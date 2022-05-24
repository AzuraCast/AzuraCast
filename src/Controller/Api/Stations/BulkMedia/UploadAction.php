<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\BulkMedia;

use App\Entity;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

use function count;
use function str_starts_with;

final class UploadAction
{
    private const ALLOWED_MEDIA_FIELDS = [
        'title',
        'artist',
        'album',
        'genre',
        'lyrics',
        'isrc',
        'amplify',
        'fade_overlap',
        'fade_in',
        'fade_out',
        'cue_in',
        'cue_out',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\CustomFieldRepository $customFieldRepo,
        private readonly Entity\Repository\StationPlaylistRepository $playlistRepo,
        private readonly Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        private readonly Serializer $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        // Handle Flow upload.
        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        // Lookup tables for later.
        $mediaStorage = $station->getMediaStorageLocation();

        $mediaByPath = [];
        $mediaByUniqueId = [];

        $mediaInStorageLocation = $this->em->createQuery(
            <<<DQL
            SELECT sm.id, sm.unique_id, sm.path
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $mediaStorage)
            ->getArrayResult();

        foreach ($mediaInStorageLocation as $mediaRow) {
            $mediaByPath[md5($mediaRow['path'])] = $mediaRow['id'];
            $mediaByUniqueId[$mediaRow['unique_id']] = $mediaRow['id'];
        }

        $customFieldShortNames = [];
        foreach ($this->customFieldRepo->fetchArray() as $row) {
            $customFieldShortNames[$row['short_name']] = $row['id'];
        }

        $playlistsByName = [];
        foreach ($this->playlistRepo->getAllForStation($station) as $playlist) {
            $shortName = Entity\StationPlaylist::generateShortName($playlist->getName());
            $playlistsByName[$shortName] = $playlist->getIdRequired();
        }

        // Read and process CSV.
        $csvPath = $flowResponse->getUploadedPath();

        $reader = Reader::createFromPath($csvPath);
        $reader->setHeaderOffset(0);

        $processed = 0;
        $importResults = [];

        $i = 0;
        $batchSize = 50;

        foreach ($reader->getRecords() as $row) {
            $row = (array)$row;
            if (isset($row['id'], $mediaByUniqueId[$row['id']])) {
                $mediaId = $mediaByUniqueId[$row['id']];
            } elseif (isset($row['path'], $mediaByPath[md5($row['path'])])) {
                $mediaId = $mediaByPath[md5($row['path'])];
            } else {
                continue;
            }

            $record = $this->em->find(Entity\StationMedia::class, $mediaId);
            if (!$record instanceof Entity\StationMedia) {
                continue;
            }

            unset($row['id'], $row['path']);

            $importResult = [
                'id' => $record->getIdRequired(),
                'title' => $record->getTitle(),
                'artist' => $record->getArtist(),
                'success' => false,
                'error' => null,
            ];

            try {
                $rowResult = $this->processRow(
                    $record,
                    $row,
                    $customFieldShortNames,
                    $playlistsByName
                );

                $importResult['success'] = $rowResult;
                if ($rowResult) {
                    $processed++;
                }
            } catch (Throwable $e) {
                $importResult['success'] = false;
                $importResult['error'] = $e->getMessage();
            }

            $importResults[] = $importResult;

            $i++;
            if (0 === $i % $batchSize) {
                $this->clearMemory();
            }
        }

        $this->clearMemory();

        @unlink($csvPath);

        return $response->withJson(
            new Entity\Api\StationPlaylistImportResult(
                message: sprintf(__('%d files processed.'), $processed),
                import_results: $importResults
            )
        );
    }

    private function processRow(
        Entity\StationMedia $record,
        array $row,
        array $customFieldShortNames,
        array $playlistsByName
    ): bool {
        $mediaRow = [];
        $hasPlaylists = false;
        $playlists = [];
        $hasCustomFields = false;
        $customFields = [];

        foreach ($row as $key => $value) {
            if ('' === $value) {
                $value = null;
            }

            if (in_array($key, self::ALLOWED_MEDIA_FIELDS, true)) {
                $mediaRow[$key] = $value;
            } elseif (str_starts_with($key, 'custom_field_')) {
                $fieldName = str_replace('custom_field_', '', $key);
                if (isset($customFieldShortNames[$fieldName])) {
                    $hasCustomFields = true;
                    $customFields[$customFieldShortNames[$fieldName]] = $value;
                }
            } elseif ('playlists' === $key) {
                $hasPlaylists = true;
                if (null !== $value) {
                    foreach (explode(',', $value) as $playlistName) {
                        $playlistShortName = Entity\StationPlaylist::generateShortName($playlistName);
                        if (isset($playlistsByName[$playlistShortName])) {
                            $playlists[] = $playlistsByName[$playlistShortName];
                        }
                    }
                }
            }
        }

        if (empty($mediaRow) && !$hasPlaylists && !$hasCustomFields) {
            return false;
        }

        if (!empty($mediaRow)) {
            $this->serializer->denormalize(
                $mediaRow,
                Entity\StationMedia::class,
                context: [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $record,
                ]
            );

            $errors = $this->validator->validate($record);
            if (count($errors) > 0) {
                throw ValidationException::fromValidationErrors($errors);
            }

            $this->em->persist($record);
            $this->em->flush();
        }

        if ($hasPlaylists) {
            $this->spmRepo->clearPlaylistsFromMedia($record);
            foreach ($playlists as $playlistId) {
                $playlist = $this->em->find(Entity\StationPlaylist::class, $playlistId);
                if ($playlist instanceof Entity\StationPlaylist) {
                    $this->spmRepo->addMediaToPlaylist($record, $playlist);
                }
            }
        }

        if ($hasCustomFields) {
            $customFields = array_filter($customFields);
            $this->customFieldRepo->setCustomFields($record, $customFields);
        }

        return true;
    }

    private function clearMemory(): void
    {
        $this->em->clear();
        gc_collect_cycles();
    }
}
