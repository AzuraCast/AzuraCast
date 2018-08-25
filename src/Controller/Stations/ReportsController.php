<?php
namespace App\Controller\Stations;

use App\Cache;
use App\Sync\Task\RadioAutomation;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class ReportsController
{
    /** @var EntityManager */
    protected $em;

    /** @var RadioAutomation */
    protected $sync_automation;

    /**
     * ReportsController constructor.
     * @param EntityManager $em
     * @param RadioAutomation $sync_automation
     */
    public function __construct(EntityManager $em, RadioAutomation $sync_automation)
    {
        $this->em = $em;
        $this->sync_automation = $sync_automation;
    }

    public function timelineAction(Request $request, Response $response): Response
    {
        return $request->getView()->renderToResponse($response, 'stations/reports/timeline');
    }

    public function performanceAction(Request $request, Response $response, $station_id, $format = 'html'): Response
    {
        $station = $request->getStation();

        $automation_config = (array)$station->getAutomationSettings();

        if (isset($automation_config['threshold_days'])) {
            $threshold_days = (int)$automation_config['threshold_days'];
        } else {
            $threshold_days = RadioAutomation::DEFAULT_THRESHOLD_DAYS;
        }

        $report_data = $this->sync_automation->generateReport($station, $threshold_days);

        // Do not show songs that are not in playlists.
        $report_data = array_filter($report_data, function ($media) {
            if (empty($media['playlists'])) {
                return false;
            }

            return true;
        });

        if ($format === 'csv') {
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
            $csv_filename = $station->getShortName() . '_media_' . date('Ymd') . '.csv';

            return $response->renderStringAsFile($csv_file, 'text/csv', $csv_filename);
        }

        if ($format === 'json') {
            return $response->withJson($report_data);
        }

        return $request->getView()->renderToResponse($response, 'stations/reports/performance', [
            'report_data' => $report_data,
        ]);
    }

    public function duplicatesAction(Request $request, Response $response, $station_id): Response
    {
        $media_raw = $this->em->createQuery('SELECT sm, s, spm, sp FROM '.Entity\StationMedia::class.' sm JOIN sm.song s LEFT JOIN sm.playlist_items spm LEFT JOIN spm.playlist sp WHERE sm.station_id = :station_id ORDER BY sm.mtime ASC')
            ->setParameter('station_id', $station_id)
            ->getArrayResult();

        $dupes = [];
        $songs_to_compare = [];

        // Find exact duplicates and sort other songs into a searchable array.
        foreach ($media_raw as $media_row) {
            foreach($media_row['playlist_items'] as $playlist_item) {
                $media_row['playlists'][] = $playlist_item['playlist'];
            }

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

        return $request->getView()->renderToResponse($response, 'stations/reports/duplicates', [
            'dupes' => $dupes,
        ]);
    }

    public function deletedupeAction(Request $request, Response $response, $station_id, $media_id): Response
    {
        $media = $this->em->getRepository(Entity\StationMedia::class)->findOneBy([
            'id' => $media_id,
            'station_id' => $station_id
        ]);

        if ($media instanceof Entity\StationMedia) {
            $path = $media->getFullPath();
            @unlink($path);

            $this->em->remove($media);
            $this->em->flush();

            $request->getSession()->flash('<b>Duplicate file deleted!</b>', 'green');
        }

        return $response->withRedirect($request->getRouter()->named('stations:reports:duplicates', ['station' => $station_id]));
    }

    public function listenersAction(Request $request, Response $response): Response
    {
        $view = $request->getView();

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $analytics_level = $settings_repo->getSetting('analytics', Entity\Analytics::LEVEL_ALL);

        if ($analytics_level !== Entity\Analytics::LEVEL_ALL) {
            return $view->renderToResponse($response, 'stations/reports/restricted');
        }

        return $view->renderToResponse($response, 'stations/reports/listeners');
    }
}
