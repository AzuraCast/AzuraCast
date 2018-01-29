<?php
namespace Controller\Stations;

use Entity;
use App\Http\Request;
use App\Http\Response;

class ReportsController extends BaseController
{
    protected function permissions()
    {
        return $this->acl->isAllowed('view station reports', $this->station->getId());
    }

    public function timelineAction(Request $request, Response $response): Response
    {
        $songs_played_raw = $this->_getEligibleHistory();

        $songs = [];
        foreach ($songs_played_raw as $song_row) {
            // Song has no recorded ending.
            if ($song_row['timestamp_end'] == 0) {
                continue;
            }

            $song_row['stat_start'] = $song_row['listeners_start'];
            $song_row['stat_end'] = $song_row['listeners_end'];
            $song_row['stat_delta'] = $song_row['delta_total'];

            $songs[] = $song_row;
        }

        $format = $this->getParam('format', 'html');
        if ($format == 'csv') {
            $this->doNotRender();

            $export_all = [];
            $export_all[] = [
                'Date',
                'Time',
                'Listeners',
                'Delta',
                'Likes',
                'Dislikes',
                'Track',
                'Artist',
                'Playlist'
            ];

            foreach ($songs as $song_row) {
                $export_row = [
                    date('Y-m-d', $song_row['timestamp_start']),
                    date('g:ia', $song_row['timestamp_start']),
                    $song_row['stat_start'],
                    $song_row['stat_delta'],
                    $song_row['score_likes'],
                    $song_row['score_dislikes'],
                    $song_row['song']['title'] ?: $song_row['song']['text'],
                    $song_row['song']['artist'],
                    $song_row['playlist']['name'] ?? '',
                ];

                $export_all[] = $export_row;
            }

            $csv_file = \App\Export::csv($export_all);
            $csv_filename = $this->station->getShortName() . '_timeline_' . date('Ymd') . '.csv';

            return $this->renderStringAsFile($csv_file, 'text/csv', $csv_filename);
        } else {
            $songs = array_reverse($songs);
            $this->view->songs = $songs;
        }
    }

    public function performanceAction(Request $request, Response $response): Response
    {
        $automation_config = (array)$this->station->getAutomationSettings();

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

    public function duplicatesAction(Request $request, Response $response): Response
    {
        $media_raw = $this->em->createQuery('SELECT sm, s, sp FROM Entity\StationMedia sm JOIN sm.song s LEFT JOIN sm.playlists sp WHERE sm.station_id = :station_id ORDER BY sm.mtime ASC')
            ->setParameter('station_id', $this->station->getId())
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

    public function deletedupeAction(Request $request, Response $response): Response
    {
        $media_id = (int)$this->getParam('media_id');

        $media = $this->em->getRepository(Entity\StationMedia::class)->findOneBy([
            'id' => $media_id,
            'station_id' => $this->station->getId()
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

    public function listenersAction(Request $request, Response $response): Response
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
}