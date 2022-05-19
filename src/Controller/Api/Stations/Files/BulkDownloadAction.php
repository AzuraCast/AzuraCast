<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Writer;
use Psr\Http\Message\ResponseInterface;

class BulkDownloadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        CustomFieldRepository $customFieldRepo,
        StationPlaylistRepository $playlistRepo,
    ): ResponseInterface {
        $station = $request->getStation();

        $customFields = [];
        foreach ($customFieldRepo->fetchArray() as $row) {
            $customFields[$row['id']] = $row['short_name'];
        }

        $playlistsById = [];
        foreach ($playlistRepo->getAllForStation($station) as $playlist) {
            $playlistsById[$playlist->getIdRequired()] = $playlist->getName();
        }

        $query = $em->createQuery(
            <<<DQL
                SELECT sm, spm, smcf
                FROM App\Entity\StationMedia sm
                LEFT JOIN sm.playlists spm
                LEFT JOIN sm.custom_fields smcf
                WHERE sm.storage_location = :storageLocation
                GROUP BY sm.id
                DQL
        )->setParameter('storageLocation', $station->getMediaStorageLocation());

        $filename = $station->getShortName() . '_all_media.csv';
        $tempFile = File::generateTempPath($filename);
        $csv = Writer::createFromPath($tempFile, 'w+');
        $csv->setOutputBOM($csv::BOM_UTF8);

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

        try {
            return $response->withFileDownload($tempFile, $filename, 'text/csv');
        } finally {
            @unlink($filename);
        }
    }
}
