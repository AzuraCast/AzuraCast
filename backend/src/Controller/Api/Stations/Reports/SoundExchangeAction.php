<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Song;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\MusicBrainz;
use App\Utilities\Types;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Produce a report in SoundExchange (the US webcaster licensing agency) format.
 */
#[OA\Get(
    path: '/station/{station_id}/reports/soundexchange',
    operationId: 'getStationSoundExchangeReport',
    summary: 'Generate a SoundExchange royalty report.',
    tags: [OpenApi::TAG_STATIONS_REPORTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'start_date',
            in: 'query',
            required: false,
            schema: new OA\Schema(
                type: 'string',
                format: 'date'
            )
        ),
        new OA\Parameter(
            name: 'end_date',
            in: 'query',
            required: false,
            schema: new OA\Schema(
                type: 'string',
                format: 'date'
            )
        ),
    ],
    responses: [
        new OpenApi\Response\SuccessWithDownload(
            description: 'Success',
            content: new OA\MediaType(
                mediaType: 'text/plain',
                schema: new OA\Schema(
                    description: 'A CSV report for the given time range.',
                    type: 'string',
                    format: 'binary'
                )
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
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

        // NOTE: These are valid uses of shiftTimezone.
        try {
            $startDate = CarbonImmutable::createFromFormat('Y-m-d', $data['start_date'], $tzObject)
                ?->shiftTimezone($tzObject)
                ?->startOfDay();
        } catch (InvalidFormatException) {
            $startDate = null;
        }

        try {
            $endDate = CarbonImmutable::createFromFormat('Y-m-d', $data['end_date'], $tzObject)
                ?->shiftTimezone($tzObject)
                ?->endOfDay();
        } catch (InvalidFormatException) {
            $endDate = null;
        }

        if (null === $startDate || null === $endDate) {
            throw new InvalidArgumentException('Invalid start/end date.');
        }

        $fetchIsrc = Types::bool($data['fetch_isrc'] ?? null, false, true);

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
            ->setParameter('storageLocation', $station->media_storage_location)
            ->getArrayResult();

        $mediaById = array_column($allMedia, null, 'id');

        $historyRows = $this->em->createQuery(
            <<<'DQL'
                SELECT sh.song_id AS song_id, sh.text, sh.artist, sh.album, sh.title, 
                    IDENTITY(sh.media) AS media_id, COUNT(sh.id) AS plays,
                    SUM(sh.unique_listeners) AS unique_listeners
                FROM App\Entity\SongHistory sh
                WHERE sh.station = :station
                AND sh.timestamp_start <= :time_end
                AND sh.timestamp_end >= :time_start
                AND sh.song_id NOT IN (:ignoredSongIds)
                GROUP BY sh.song_id
            DQL
        )->setParameter('station', $station)
            ->setParameter('time_start', $startDate)
            ->setParameter('time_end', $endDate)
            ->setParameter('ignoredSongIds', [
                Song::OFFLINE_SONG_ID,
            ])->getArrayResult();

        // Assemble report items
        $stationName = $station->name;

        $setIsrcQuery = $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.isrc = :isrc
                WHERE sm.id = :media_id
            DQL
        );

        foreach ($historyRows as $historyRow) {
            if (!empty($historyRow['media_id']) && isset($mediaById[$historyRow['media_id']])) {
                $songRow = $mediaById[$historyRow['media_id']];

                // Try to find the ISRC if it's not already listed.
                if ($fetchIsrc && empty($songRow['isrc'])) {
                    $isrc = $this->findISRC($songRow);
                    $songRow['isrc'] = $isrc;

                    if (null !== $isrc) {
                        $setIsrcQuery->setParameter('isrc', $isrc)
                            ->setParameter('media_id', $songRow['media_id'])
                            ->execute();
                    }
                }
            } else {
                $songRow = $historyRow;
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
        $exportFilename = strtoupper($station->short_name)
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
