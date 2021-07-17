<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Config;
use App\Entity;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\MusicBrainz;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Produce a report in SoundExchange (the US webcaster licensing agency) format.
 */
class SoundExchangeController
{
    protected array $form_config;

    public function __construct(
        protected EntityManagerInterface $em,
        protected MusicBrainz $musicBrainz,
        Config $config
    ) {
        $this->form_config = $config->get('forms/report/soundexchange');
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $tzObject = $station->getTimezoneObject();

        $startDate = CarbonImmutable::parse('first day of last month', $tzObject);
        $endDate = CarbonImmutable::parse('last day of last month', $tzObject);

        $form = new Form($this->form_config);
        $form->populate(
            [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        );

        if ($form->isValid($request)) {
            $data = $form->getValues();

            $startDate = CarbonImmutable::parse($data['start_date'] . ' 00:00:00', $tzObject);
            $endDate = CarbonImmutable::parse($data['end_date'] . ' 23:59:59', $tzObject);

            $fetchIsrc = $data['fetch_isrc'];

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

            $all_media = $this->em->createQuery(
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

            $media_by_id = array_column($all_media, null, 'id');

            $history_rows = $this->em->createQuery(
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

            $history_rows_by_id = array_column($history_rows, null, 'media_id');

            // Remove any reference to the "Stream Offline" song.
            $offline_song_hash = Entity\Song::createOffline()->getSongId();
            unset($history_rows_by_id[$offline_song_hash]);

            // Assemble report items
            $station_name = $station->getName();

            $set_isrc_query = $this->em->createQuery(
                <<<'DQL'
                    UPDATE App\Entity\StationMedia sm
                    SET sm.isrc = :isrc
                    WHERE sm.id = :media_id
                DQL
            );

            foreach ($history_rows_by_id as $song_id => $history_row) {
                $song_row = $media_by_id[$song_id] ?? $history_row;

                // Try to find the ISRC if it's not already listed.
                if ($fetchIsrc && empty($song_row['isrc'])) {
                    $isrc = $this->findISRC($song_row);
                    $song_row['isrc'] = $isrc;

                    if (null !== $isrc && isset($song_row['media_id'])) {
                        $set_isrc_query->setParameter('isrc', $isrc)
                            ->setParameter('media_id', $song_row['media_id'])
                            ->execute();
                    }
                }

                $export[] = [
                    $station_name,
                    'A',
                    $song_row['artist'] ?? '',
                    $song_row['title'] ?? '',
                    $song_row['isrc'] ?? '',
                    $song_row['album'] ?? '',
                    '',
                    $history_row['unique_listeners'],
                ];
            }

            // Assemble export into SoundExchange format
            $export_txt_raw = [];
            foreach ($export as $export_row) {
                foreach ($export_row as $i => $export_col) {
                    if (!is_numeric($export_col)) {
                        $export_row[$i] = '^' . str_replace(['^', '|'], ['', ''], strtoupper($export_col)) . '^';
                    }
                }
                $export_txt_raw[] = implode('|', $export_row);
            }
            $export_txt = implode("\n", $export_txt_raw);

            // Example: WABC01012009-31012009_A.txt
            $export_filename = strtoupper($station->getShortName())
                . $startDate->format('dmY') . '-'
                . $endDate->format('dmY') . '_A.txt';

            return $response->renderStringAsFile($export_txt, 'text/plain', $export_filename);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('SoundExchange Report'),
        ]);
    }

    protected function findISRC(array $song_row): ?string
    {
        $song = Entity\Song::createFromArray($song_row);

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
