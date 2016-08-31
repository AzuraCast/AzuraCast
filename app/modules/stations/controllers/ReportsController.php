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
                return $this->response->setJsonContent($report_data);
                break;

            case 'html':
            default:
                $this->view->report_data = $report_data;
                break;
        }

        return true;
    }
}