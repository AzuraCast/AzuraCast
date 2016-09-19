<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\StationMedia;
use Entity\StationPlaylist;

class ReportsController extends BaseController
{
    public function performanceAction()
    {
        $automation_config = (array)$this->station->automation_settings;

        if (isset($automation_config['threshold_days']))
            $threshold_days = (int)$automation_config['threshold_days'];
        else
            $threshold_days = \App\Radio\Automation::DEFAULT_THRESHOLD_DAYS;

        $report_data = \App\Radio\Automation::generateReport($this->station, $threshold_days);

        // Do not show songs that are not in playlists.
        $report_data = array_filter($report_data, function($media) {
            if (empty($media['playlists']))
                return false;

            return true;
        });

        switch(strtolower($this->getParam('format')))
        {
            case 'csv':
                $this->doNotRender();

                $export_csv = [[
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
                ]];

                foreach($report_data as $row)
                {
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
                        $row['percent_plays'].'%',
                        $row['ratio'],
                    ];
                }

                $filename = $this->station->getShortName().'_media_'.date('Ymd').'.csv';
                \App\Export::csv($export_csv, TRUE, $filename);
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

        $dupes = array();
        $songs_to_compare = array();

        // Find exact duplicates and sort other songs into a searchable array.
        foreach($media_raw as $media_row)
        {
            if (isset($songs_to_compare[$media_row['song_id']]))
                $dupes[] = [$songs_to_compare[$media_row['song_id']], $media_row];
            else
                $songs_to_compare[$media_row['song_id']] = $media_row;
        }

        foreach($songs_to_compare as $song_id => $media_row)
        {
            unset($songs_to_compare[$song_id]);

            $media_text = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $media_row['song']['text']));

            $song_dupes = array();
            foreach($songs_to_compare as $search_song_id => $search_media_row)
            {
                $search_media_text = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $search_media_row['song']['text']));
                $similarity = levenshtein($media_text, $search_media_text);

                if ($similarity <= 5)
                    $song_dupes[] = $search_media_row;
            }

            if (count($song_dupes) > 0)
            {
                $song_dupes[] = $media_row;
                $dupes[] = $song_dupes;
            }
        }

        $this->view->dupes = $dupes;
    }

    public function deletedupeAction()
    {
        $media_id = (int)$this->getParam('media_id');

        $media = StationMedia::getRepository()->findOneBy(['id' => $media_id, 'station_id' => $this->station->id]);

        if ($media instanceof StationMedia)
        {
            $path = $media->getFullPath();
            @unlink($path);

            $media->delete();

            $this->alert('<b>Duplicate file deleted!</b>', 'green');
        }

        return $this->redirectFromHere(['action' => 'duplicates', 'media_id' => null]);
    }
}