<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\BulkMedia;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationMediaMetadata;
use App\Http\Response;
use App\Http\ServerRequest;
use League\Csv\Writer;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class DownloadAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly StationPlaylistRepository $playlistRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $customFields = [];
        foreach ($this->customFieldRepo->fetchArray() as $row) {
            $customFields[$row['id']] = $row['short_name'];
        }

        $playlistsById = [];
        foreach ($this->playlistRepo->getAllForStation($station) as $playlist) {
            $playlistsById[$playlist->getIdRequired()] = $playlist->getName();
        }

        $query = $this->em->createQuery(
            <<<DQL
                SELECT sm, spm, smcf
                FROM App\Entity\StationMedia sm
                LEFT JOIN sm.playlists spm
                LEFT JOIN sm.custom_fields smcf
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->getMediaStorageLocation());

        $filename = $station->getShortName() . '_all_media.csv';

        if (!($tempFile = tmpfile())) {
            throw new RuntimeException('Could not create temp file.');
        }
        $csv = Writer::createFromStream($tempFile);

        $extraMetadataFields = StationMediaMetadata::getFields();

        /*
         * NOTE: These field names should correspond with DB property names when converted into short_names.
         * i.e. Fade Overlap -> fade_overlap
         */
        $headerRow = [
            'id',
            'path',
            'title',
            'artist',
            'album',
            'genre',
            'lyrics',
            'isrc',
            'playlists',
            ...$extraMetadataFields,
        ];

        foreach ($customFields as $customField) {
            $headerRow[] = 'custom_field_' . $customField;
        }

        $csv->insertOne($headerRow);

        /** @var array $row */
        foreach ($query->getArrayResult() as $row) {
            $extraMetadata = [];
            foreach ($extraMetadataFields as $fieldName) {
                $extraMetadata[] = $row['extra_metadata'][$fieldName] ?? '';
            }

            $customFieldsById = [];
            foreach ($row['custom_fields'] ?? [] as $rowCustomField) {
                $customFieldsById[$rowCustomField['field_id']] = $rowCustomField['value'];
            }

            $playlists = [];
            foreach ($row['playlists'] ?? [] as $rowPlaylistMedia) {
                if (isset($playlistsById[$rowPlaylistMedia['playlist_id']])) {
                    $playlists[] = $playlistsById[$rowPlaylistMedia['playlist_id']];
                }
            }

            $bodyRow = [
                $row['unique_id'] ?? '',
                $row['path'] ?? '',
                $row['title'] ?? '',
                $row['artist'] ?? '',
                $row['album'] ?? '',
                $row['genre'] ?? '',
                $row['lyrics'] ?? '',
                $row['isrc'] ?? '',
                implode(', ', $playlists),
                ...$extraMetadata,
            ];

            foreach ($customFields as $customFieldId => $customField) {
                $bodyRow[] = $customFieldsById[$customFieldId] ?? '';
            }

            $csv->insertOne($bodyRow);
        }

        return $response->withFileDownload($tempFile, $filename, 'text/csv');
    }
}
