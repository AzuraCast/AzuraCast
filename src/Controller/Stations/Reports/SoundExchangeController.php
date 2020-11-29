<?php

namespace App\Controller\Stations\Reports;

use App\Config;
use App\Entity;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Produce a report in SoundExchange (the US webcaster licensing agency) format.
 */
class SoundExchangeController
{
    protected EntityManagerInterface $em;

    protected Client $http_client;

    protected array $form_config;

    public function __construct(EntityManagerInterface $em, Client $http_client, Config $config)
    {
        $this->em = $em;
        $this->form_config = $config->get('forms/report/soundexchange');
        $this->http_client = $http_client;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $form = new Form($this->form_config);
        $form->populate([
            'start_date' => date('Y-m-d', strtotime('first day of last month')),
            'end_date' => date('Y-m-d', strtotime('last day of last month')),
        ]);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $start_date = strtotime($data['start_date'] . ' 00:00:00');
            $end_date = strtotime($data['end_date'] . ' 23:59:59');

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

            $all_media = $this->em->createQuery(/** @lang DQL */ 'SELECT sm
                FROM App\Entity\StationMedia sm
                WHERE sm.station_id = :station_id')
                ->setParameter('station_id', $station->getId())
                ->getArrayResult();

            $media_by_id = [];
            foreach ($all_media as $media_row) {
                $media_by_id[$media_row['song_id']] = $media_row;
            }

            $history_rows = $this->em
                ->createQuery(/** @lang DQL */
                    'SELECT
                        sh.song_id AS song_id,
                        sh.text,
                        sh.artist,
                        sh.title,
                        COUNT(sh.id) AS plays,
                        SUM(sh.unique_listeners) AS unique_listeners
                    FROM App\Entity\SongHistory sh
                    WHERE sh.station_id = :station_id
                    AND sh.timestamp_start <= :time_end
                    AND sh.timestamp_end >= :time_start
                    GROUP BY sh.song_id'
                )
                ->setParameter('station_id', $station->getId())
                ->setParameter('time_start', $start_date)
                ->setParameter('time_end', $end_date)
                ->getArrayResult();

            $history_rows_by_id = [];
            foreach ($history_rows as $history_row) {
                $history_rows_by_id[$history_row['song_id']] = $history_row;
            }

            // Remove any reference to the "Stream Offline" song.
            $offline_song_hash = Entity\Song::getSongHash('stream_offline');
            unset($history_rows_by_id[$offline_song_hash]);

            // Assemble report items
            $station_name = $station->getName();

            $set_isrc_query = $this->em->createQuery(/** @lang DQL */ 'UPDATE
                App\Entity\StationMedia sm
                SET sm.isrc = :isrc
                WHERE sm.song_id = :song_id
                AND sm.station_id = :station_id')
                ->setParameter('station_id', $station->getId());

            foreach ($history_rows_by_id as $song_id => $history_row) {
                $song_row = $media_by_id[$song_id] ?? $history_row;

                // Try to find the ISRC if it's not already listed.
                if (array_key_exists('isrc', $song_row) && $song_row['isrc'] === null) {
                    $isrc = $this->findISRC($song_row);
                    $song_row['isrc'] = $isrc;

                    $set_isrc_query->setParameter('isrc', $isrc)
                        ->setParameter('song_id', $song_id)
                        ->execute();
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
                . date('dmY', $start_date) . '-'
                . date('dmY', $end_date) . '_A.txt';

            return $response->renderStringAsFile($export_txt, 'text/plain', $export_filename);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('SoundExchange Report'),
        ]);
    }

    protected function findISRC($song_row): string
    {
        // Temporarily disable this feature, as the Spotify API now requires authentication for all requests.

        /*
        $query_parts = [];
        if (!empty($song_row['artist'])) {
            $query_parts[] = 'artist:"' . $song_row['artist'] . '"';
        }
        if (!empty($song_row['album'])) {
            $query_parts[] = 'album:"' . $song_row['album'] . '"';
        }
        $query_parts[] = $song_row['title'];

        $search_response = $this->http_client->get('https://api.spotify.com/v1/search', [
            'query' => [
                'q' => implode(' ', $query_parts),
                'type' => 'track',
                'limit' => 1,
            ],
        ]);

        $search_result_raw = $search_response->getBody()->getContents();
        $search_result = @json_decode($search_result_raw, true);

        if (!empty($search_result['tracks']['items'])) {
            $track = $search_result['tracks']['items'][0];
            return str_replace('-', '', $track['external_ids']['isrc']);
        }
        */

        return '';
    }
}
