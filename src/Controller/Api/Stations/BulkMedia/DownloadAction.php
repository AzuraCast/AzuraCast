<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\BulkMedia;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Repository\StationPlaylistRepository;
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
            'amplify',
            'fade_overlap',
            'fade_in',
            'fade_out',
            'cue_in',
            'cue_out',
            'playlists',
        ];

        foreach ($customFields as $customField) {
            $headerRow[] = 'custom_field_' . $customField;
        }

        $csv->insertOne($headerRow);

        foreach ($query->getArrayResult() as $row) {
            $customFieldsById = [];
            foreach ($row['custom_fields'] as $rowCustomField) {
                $customFieldsById[$rowCustomField['field_id']] = $rowCustomField['value'];
            }

            $playlists = [];
            foreach ($row['playlists'] as $rowPlaylistMedia) {
                if (isset($playlistsById[$rowPlaylistMedia['playlist_id']])) {
                    $playlists[] = $playlistsById[$rowPlaylistMedia['playlist_id']];
                }
            }

            $bodyRow = [
                $row['unique_id'],
                $row['path'],
                $row['title'],
                $row['artist'],
                $row['album'],
                $row['genre'],
                $row['lyrics'],
                $row['isrc'],
                $row['amplify'],
                $row['fade_overlap'],
                $row['fade_in'],
                $row['fade_out'],
                $row['cue_in'],
                $row['cue_out'],
                implode(', ', $playlists),
            ];

            foreach ($customFields as $customFieldId => $customField) {
                $bodyRow[] = $customFieldsById[$customFieldId] ?? '';
            }

            $csv->insertOne($bodyRow);
        }

        return $response->withFileDownload($tempFile, $filename, 'text/csv');
    }
}
