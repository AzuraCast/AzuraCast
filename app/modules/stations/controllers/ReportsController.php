<?php
namespace Controller\Stations;

use Entity;

class ReportsController extends BaseController
{
    protected function permissions()
    {
        return $this->acl->isAllowed('view station reports', $this->station->id);
    }

    public function performanceAction()
    {
        $automation_config = (array)$this->station->automation_settings;

        if (isset($automation_config['threshold_days'])) {
            $threshold_days = (int)$automation_config['threshold_days'];
        } else {
            $threshold_days = \AzuraCast\Sync\RadioAutomation::DEFAULT_THRESHOLD_DAYS;
        }

        $automation = new \AzuraCast\Sync\RadioAutomation($this->di);
        $report_data = $automation->generateReport($this->station, $threshold_days);

        // Do not show songs that are not in playlists.
        $report_data = array_filter($report_data, function ($media) {
            if (empty($media['playlists'])) {
                return false;
            }

            return true;
        });

        switch (strtolower($this->getParam('format'))) {
            case 'csv':
                $this->doNotRender();

                $export_csv = [
                    [
                        'Song Title',
                        'Song Artist',
                        'Filename',
                        'Length',
                        'Current Playlist',
                        'Delta Joins',
                        'Delta Losses',
                        'Delta Total',
                        'Play Count',
                        'Play Percentage',
                        'Weighted Ratio',
                    ]
                ];

                foreach ($report_data as $row) {
                    $export_csv[] = [
                        $row['title'],
                        $row['artist'],
                        $row['path'],
                        $row['length'],

                        implode('/', $row['playlists']),
                        $row['delta_positive'],
                        $row['delta_negative'],
                        $row['delta_total'],

                        $row['num_plays'],
                        $row['percent_plays'] . '%',
                        $row['ratio'],
                    ];
                }

                $csv_file = \App\Export::csv($export_csv);
                $csv_filename = $this->station->getShortName() . '_media_' . date('Ymd') . '.csv';

                return $this->renderStringAsFile($csv_file, 'text/csv', $csv_filename);
                break;

            case 'json':
                $this->response->getBody()->write(json_encode($report_data));

                return $this->response;
                break;

            case 'html':
            default:
                $this->view->report_data = $report_data;
                break;
        }

        return true;
    }

    public function duplicatesAction()
    {
        $media_raw = $this->em->createQuery('SELECT sm, s, sp FROM Entity\StationMedia sm JOIN sm.song s LEFT JOIN sm.playlists sp WHERE sm.station_id = :station_id ORDER BY sm.mtime ASC')
            ->setParameter('station_id', $this->station->id)
            ->getArrayResult();

        $dupes = [];
        $songs_to_compare = [];

        // Find exact duplicates and sort other songs into a searchable array.
        foreach ($media_raw as $media_row) {
            if (isset($songs_to_compare[$media_row['song_id']])) {
                $dupes[] = [$songs_to_compare[$media_row['song_id']], $media_row];
            } else {
                $songs_to_compare[$media_row['song_id']] = $media_row;
            }
        }

        foreach ($songs_to_compare as $song_id => $media_row) {
            unset($songs_to_compare[$song_id]);

            $media_text = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $media_row['song']['text']));

            $song_dupes = [];
            foreach ($songs_to_compare as $search_song_id => $search_media_row) {
                $search_media_text = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $search_media_row['song']['text']));
                $similarity = levenshtein($media_text, $search_media_text);

                if ($similarity <= 5) {
                    $song_dupes[] = $search_media_row;
                }
            }

            if (count($song_dupes) > 0) {
                $song_dupes[] = $media_row;
                $dupes[] = $song_dupes;
            }
        }

        $this->view->dupes = $dupes;
    }

    public function deletedupeAction()
    {
        $media_id = (int)$this->getParam('media_id');

        $media = $this->em->getRepository(Entity\StationMedia::class)->findOneBy([
            'id' => $media_id,
            'station_id' => $this->station->id
        ]);

        if ($media instanceof Entity\StationMedia) {
            $path = $media->getFullPath();
            @unlink($path);

            $this->em->remove($media);
            $this->em->flush();

            $this->alert('<b>Duplicate file deleted!</b>', 'green');
        }

        return $this->redirectFromHere(['action' => 'duplicates', 'media_id' => null]);
    }

    public function listenersAction()
    {
        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        if (!empty($_POST['gmaps_api_key'])) {

            $settings_repo->setSetting('gmaps_api_key', trim($_POST['gmaps_api_key']));

            $this->alert('<b>Google Maps API key updated!</b>', 'green');
            return $this->redirectHere();
        }

        $gmaps_api_key = $settings_repo->getSetting('gmaps_api_key', null);
        $this->view->gmaps_api_key = $gmaps_api_key;

        return null;
    }

    /**
     * Produce a report in SoundExchange (the US webcaster licensing agency) format.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function soundexchangeAction()
    {
        $form_config = $this->config->forms->report_soundexchange;
        $form = new \App\Form($form_config);

        $form->setDefaults([
            'start_date' => date('Y-m-d', strtotime('first day of last month')),
            'end_date' => date('Y-m-d', strtotime('last day of last month')),
        ]);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $this->doNotRender();

            $data = $form->getValues();

            $start_date = strtotime($data['start_date'].' 00:00:00');
            $end_date = strtotime($data['end_date'].' 23:59:59');

            $export = [[
                'NAME_OF_SERVICE',
                'TRANSMISSION_CATEGORY',
                'FEATURED_ARTIST',
                'SOUND_RECORDING_TITLE',
                'ISRC',
                'ALBUM_TITLE',
                'MARKETING_LABEL',
                'ACTUAL_TOTAL_PERFORMANCES',
                'AGGREGATE_TUNING_HOURS',
                'CHANNEL_OR_PROGRAM_NAME',
                'PLAY_FREQUENCY',
            ]];

            // Pull all station media for quick referencing.
            $all_media = $this->em->createQuery('SELECT sm, sp
                FROM Entity\StationMedia sm
                LEFT JOIN sm.playlists sp
                WHERE sm.station_id = :station_id')
                ->setParameter('station_id', $this->station->id)
                ->getArrayResult();

            $media_by_id = [];
            foreach($all_media as $media_row) {
                if (!empty($media_row['playlists'])) {
                    $media_row['playlist'] = $media_row['playlists'][0]['name'];
                    unset($media_row['playlists']);
                }

                $media_by_id[$media_row['song_id']] = $media_row;
            }

            // Pull Aggregate Tuning Hours (ATH) totals
            $ath = (float)$this->em->createQuery('SELECT
                AVG(sh.listeners_end) * ((MAX(sh.timestamp_end) - MIN(sh.timestamp_end)) / 3600)
                FROM Entity\SongHistory sh
                WHERE sh.station_id = :station_id
                AND sh.timestamp_start <= :end
                AND sh.timestamp_end >= :start')
                ->setParameter('station_id', $this->station->id)
                ->setParameter('start', $start_date)
                ->setParameter('end', $end_date)
                ->getSingleScalarResult();

            $ath = round($ath, 2);

            // Pull all relevant history grouped by song ID.
            $history_rows = $this->em->createQuery('SELECT
                sh.song_id AS song_id, COUNT(sh.id) AS plays, SUM(sh.unique_listeners) AS unique_listeners
                FROM Entity\SongHistory sh
                WHERE sh.station_id = :station_id
                AND sh.timestamp_start <= :end
                AND sh.timestamp_end >= :start
                GROUP BY sh.song_id')
                ->setParameter('station_id', $this->station->id)
                ->setParameter('start', $start_date)
                ->setParameter('end', $end_date)
                ->getArrayResult();

            $history_rows_by_id = [];
            foreach($history_rows as $history_row) {
                $history_rows_by_id[$history_row['song_id']] = $history_row;
            }

            // Remove any reference to the "Stream Offline" song.
            $offline_song_hash = Entity\Song::getSongHash(['text' => 'stream_offline']);
            unset($history_rows_by_id[$offline_song_hash]);

            // Get all songs not found in the StationMedia library
            $not_found_songs = array_diff_key($history_rows_by_id, $media_by_id);

            if (!empty($not_found_songs)) {

                $songs_raw = $this->em->createQuery('SELECT s
                    FROM Entity\Song s
                    WHERE s.id IN (:song_ids)')
                    ->setParameter('song_ids', array_keys($not_found_songs))
                    ->getArrayResult();

                foreach($songs_raw as $song_row) {
                    $media_by_id[$song_row['id']] = $song_row;
                }
            }

            // Assemble report items
            $station_name = $this->station->name;

            $isrc_search = new \GuzzleHttp\Client(['base_uri' => 'https://api.spotify.com/v1/']);
            $set_isrc_query = $this->em->createQuery('UPDATE Entity\StationMedia sm
                SET sm.isrc = :isrc
                WHERE sm.song_id = :song_id
                AND sm.station_id = :station_id')
                ->setParameter('station_id', $this->station->id);

            foreach($history_rows_by_id as $song_id => $history_row) {

                $song_row = $media_by_id[$song_id];

                // Try to find the ISRC if it's not already listed.
                if (array_key_exists('isrc', $song_row) && $song_row['isrc'] === null) {

                    $query_parts = [];
                    if (!empty($song_row['artist'])) {
                        $query_parts[] = 'artist:"'.$song_row['artist'].'"';
                    }
                    if (!empty($song_row['album'])) {
                        $query_parts[] = 'album:"'.$song_row['album'].'"';
                    }
                    $query_parts[] = $song_row['title'];

                    $search_response = $isrc_search->get('search', [
                        'query' => [
                            'q' => implode(' ', $query_parts),
                            'type' => 'track',
                            'limit' => 1,
                        ],
                    ]);

                    if ($search_response->getStatusCode() == 200) {
                        $search_result_raw = $search_response->getBody()->getContents();
                        $search_result = @json_decode($search_result_raw, true);

                        if (!empty($search_result['tracks']['items'])) {
                            $track = $search_result['tracks']['items'][0];
                            $isrc = str_replace('-', '', $track['external_ids']['isrc']);

                            if (!empty($isrc)) {
                                $song_row['isrc'] = $isrc;

                                $set_isrc_query->setParameter('isrc', $isrc)
                                    ->setParameter('song_id', $song_id)
                                    ->execute();
                            }
                        } else {
                            $song_row['isrc'] = '';

                            $set_isrc_query->setParameter('isrc', '')
                                ->setParameter('song_id', $song_id)
                                ->execute();
                        }
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
                    $ath,
                    $song_row['playlist'] ?? 'Live Broadcasting',
                    $history_row['plays'],
                ];

            }

            // Assemble export into SoundExchange format
            $export_txt_raw = [];
            foreach($export as $export_row) {
                foreach($export_row as $i => $export_col) {
                    if (!is_numeric($export_col)) {
                        $export_row[$i] = '^'.str_replace(['^', '|'], ['', ''], strtoupper($export_col)).'^';
                    }
                }
                $export_txt_raw[] = implode('|', $export_row);
            }
            $export_txt = implode("\n", $export_txt_raw);

            // Example: WABC01012009-31012009_A.txt
            $export_filename = strtoupper($this->station->getShortName())
                . date('dmY', $start_date) . '-'
                . date('dmY', $end_date).'_A.txt';

            return $this->renderStringAsFile($export_txt, 'text/plain', $export_filename);
        }

        return $this->renderForm($form, 'edit', _('SoundExchange Report'));
    }
}