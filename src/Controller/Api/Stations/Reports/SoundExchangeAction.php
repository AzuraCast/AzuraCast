<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Song;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\MusicBrainz;
use Carbon\CarbonImmutable;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Produce a report in SoundExchange (the US webcaster licensing agency) format.
 */
final class SoundExchangeAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly MusicBrainz $musicBrainz
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $tzObject = $station->getTimezoneObject();

        $defaultStartDate = CarbonImmutable::parse('first day of last month', $tzObject)
            ->format('Y-m-d');

        $defaultEndDate = CarbonImmutable::parse('last day of last month', $tzObject)
            ->format('Y-m-d');

        $data = $request->getParams();

        $data['start_date'] ??= $defaultStartDate;
        $data['end_date'] ??= $defaultEndDate;

        $startDate = CarbonImmutable::parse($data['start_date'] . ' 00:00:00', $tzObject)
            ->shiftTimezone($tzObject);

        $endDate = CarbonImmutable::parse($data['end_date'] . ' 23:59:59', $tzObject)
            ->shiftTimezone($tzObject);

        $fetchIsrc = 'true' === ($data['fetch_isrc'] ?? 'false');

        $export = [
            [
                'NAME_OF_SERVICE',
                'TRANSMISSION_CATEGORY',
                'FEATURED_ARTIST',
                'SOUND_RECORDING_TITLE',
                'ISRC',
                'ALBUM_TITLE',
                'MARKETING_LABEL',
                'ACTUAL_TOTAL_PERFORMANCES',
            ],
        ];

        $allMedia = $this->em->createQuery(
            <<<'DQL'
                SELECT sm, spm, sp, smcf
                FROM App\Entity\StationMedia sm
                LEFT JOIN sm.custom_fields smcf
                LEFT JOIN sm.playlists spm
                LEFT JOIN spm.playlist sp
                WHERE sm.storage_location = :storageLocation
                AND sp.station IS NULL OR sp.station = :station
            DQL
        )->setParameter('station', $station)
            ->setParameter('storageLocation', $station->getMediaStorageLocation())
            ->getArrayResult();

        $mediaById = array_column($allMedia, null, 'id');

        $historyRows = $this->em->createQuery(
            <<<'DQL'
                SELECT sh.song_id AS song_id, sh.text, sh.artist, sh.title, sh.media_id, COUNT(sh.id) AS plays,
                    SUM(sh.unique_listeners) AS unique_listeners
                FROM App\Entity\SongHistory sh
                WHERE sh.station = :station
                AND sh.timestamp_start <= :time_end
                AND sh.timestamp_end >= :time_start
                GROUP BY sh.song_id
            DQL
        )->setParameter('station', $station)
            ->setParameter('time_start', $startDate->getTimestamp())
            ->setParameter('time_end', $endDate->getTimestamp())
            ->getArrayResult();

        // TODO: Fix this (not all song rows have a media_id)
        $historyRowsById = array_column($historyRows, null, 'media_id');

        // Remove any reference to the "Stream Offline" song.
        $offlineSongHash = Song::OFFLINE_SONG_ID;
        unset($historyRowsById[$offlineSongHash]);

        // Assemble report items
        $stationName = $station->getName();

        $setIsrcQuery = $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.isrc = :isrc
                WHERE sm.id = :media_id
            DQL
        );

        foreach ($historyRowsById as $songId => $historyRow) {
            $songRow = $mediaById[$songId] ?? $historyRow;

            // Try to find the ISRC if it's not already listed.
            if ($fetchIsrc && empty($songRow['isrc'])) {
                $isrc = $this->findISRC($songRow);
                $songRow['isrc'] = $isrc;

                if (null !== $isrc && isset($songRow['media_id'])) {
                    $setIsrcQuery->setParameter('isrc', $isrc)
                        ->setParameter('media_id', $songRow['media_id'])
                        ->execute();
                }
            }

            $export[] = [
                $stationName,
                'A',
                $songRow['artist'] ?? '',
                $songRow['title'] ?? '',
                $songRow['isrc'] ?? '',
                $songRow['album'] ?? '',
                '',
                $historyRow['unique_listeners'],
            ];
        }

        // Assemble export into SoundExchange format
        $exportTxtRaw = [];
        foreach ($export as $exportRow) {
            foreach ($exportRow as $i => $exportCol) {
                if (!is_numeric($exportCol)) {
                    $exportRow[$i] = '^' . str_replace(['^', '|'], ['', ''], strtoupper($exportCol)) . '^';
                }
            }
            $exportTxtRaw[] = implode('|', $exportRow);
        }
        $exportTxt = implode("\n", $exportTxtRaw);

        // Example: WABC01012009-31012009_A.txt
        $exportFilename = strtoupper($station->getShortName())
            . $startDate->format('dmY') . '-'
            . $endDate->format('dmY') . '_A.txt';

        return $response->renderStringAsFile($exportTxt, 'text/plain', $exportFilename);
    }

    private function findISRC(array $songRow): ?string
    {
        $song = Song::createFromArray($songRow);

        try {
            foreach ($this->musicBrainz->findRecordingsForSong($song, 'isrcs') as $recording) {
                if (!empty($recording['isrcs'])) {
                    return $recording['isrcs'][0];
                }
            }
            return null;
        } catch (Throwable) {
            return null;
        }
    }
}
