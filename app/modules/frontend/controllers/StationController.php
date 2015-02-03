<?php
namespace Modules\Frontend\Controllers;

use \Entity\Station;
use \Entity\StationMedia;

use \PVL\CentovaCast;

class StationController extends BaseController
{
    protected $station_id;
    protected $station;

    public function preDispatch()
    {
        parent::preDispatch();

        $station_id = (int)$this->getParam('id');
        if ($station_id)
        {
            $this->station_id = $station_id;
            $this->station = Station::find($station_id);

            $this->view->station_id = $station_id;
            $this->view->station = $this->station;
        }
    }

    public function stationRequired()
    {
        if (!($this->station instanceof Station))
            throw new \DF\Exception\DisplayOnly('Station ID is not valid.');
    }

    public function indexAction()
    {}

    public function requestAction()
    {
        $this->stationRequired();

        if (!$this->station->requests_enabled)
            throw new \DF\Exception\DisplayOnly('This station does not support requests at this time.');

        if ($this->station->requests_external_url)
            $this->redirect($this->station->requests_external_url);

        $is_supported = CentovaCast::isStationSupported($this->station);
        if (!$is_supported)
            throw new \DF\Exception\DisplayOnly('This station is not functioning properly and cannot accept requests at this time.');

        // Search redirection.
        if ($_GET)
            $this->redirectFromHere($_GET);

        // Process a request.
        if ($this->getParam('track'))
        {
            try
            {
                $track_id = (int)$this->getParam('track');
                CentovaCast::request($this->station, $track_id);

                $track = StationMedia::find($track_id);
                $this->alert('<b>Your song, "'.$track->title.'" by '.$track->artist.', has been requested.</b><br>Stay tuned to the station to hear it!', 'green');
            }
            catch(\DF\Exception $e)
            {
                $this->alert('<b>Your song could not be requested. An error occurred:</b><br>'.$e->getMessage(), 'red');
            }

            $this->redirectFromHere(array('track' => NULL));
            return;
        }

        // Most requested songs.
        $top_songs = $this->em->createQuery('SELECT sm FROM Entity\StationMedia sm WHERE sm.station_id = :station_id AND sm.requests > 0 ORDER BY sm.requests DESC')
            ->setParameter('station_id', $this->station_id)
            ->setMaxResults(10)
            ->getArrayResult();
        $this->view->top_songs = $top_songs;

        // Artist names.
        $artist_names_raw = $this->em->createQuery('SELECT DISTINCT sm.artist FROM Entity\StationMedia sm WHERE sm.station_id = :station_id ORDER BY sm.artist ASC')
            ->setParameter('station_id', $this->station_id)
            ->getArrayResult();

        $artist_names = array();
        foreach($artist_names_raw as $name)
            $artist_names[] = $name['artist'];
        $this->view->artist_names = $artist_names;

        // Paginated results.
        if ($this->hasParam('q'))
        {
            $query = $this->getParam('q');

            $media = StationMedia::search($this->station, $query);
            $this->view->page_title = 'Search Results for "'.htmlspecialchars($query).'"';
            $this->view->reset_button = true;
        }
        else if ($this->hasParam('artist'))
        {
            $artist = $this->getParam('artist');

            $media = StationMedia::getByArtist($this->station, $artist);
            $this->view->page_title = 'All Songs by '.htmlspecialchars($artist);
            $this->view->reset_button = true;
        }
        else
        {
            $media = StationMedia::getRequestable($this->station);
            $this->view->page_title = 'All Available Songs';
            $this->view->reset_button = false;
        }

        $pager = new \DF\Paginator($media, $this->getParam('page'), 50);
        $this->view->pager = $pager;
    }

    public function playlistAction()
    {
        $this->doNotRender();

        if ($this->station)
        {
            $stations = array($this->station);
        }
        else
        {
            $all_stations = Station::getStationsInCategories();
            $stations = $all_stations['audio']['stations'];
        }

        $format = strtolower($this->getParam('format', 'pls'));
        switch($format)
        {
            case "m3u":
                $m3u_lines = array();
                $m3u_lines[] = '#EXTM3U';

                $i = 0;
                foreach($stations as $station)
                {
                    foreach($station['streams'] as $stream)
                    {
                        $m3u_lines[] = '#EXTINF:' . $i . ',PVL! ' . $station['name'].': '.$stream['name'];
                        $m3u_lines[] = $stream['stream_url'];
                        $i++;
                    }
                }

                $m3u_file = implode("\r\n", $m3u_lines);

                header('Content-Type: audio/x-mpegurl');
                header('Content-Disposition: attachment; filename="pvl_stations.m3u"');
                echo $m3u_file;
            break;

            case "pls":
            default:
                $output = array();
                $output[] = '[playlist]';
                $output[] = 'NumberOfEntries='.count($stations);

                $i = 1;
                foreach($stations as $station)
                {
                    foreach($station['streams'] as $stream)
                    {
                        $output[] = 'File' . $i . '=' . $stream['stream_url'];
                        $output[] = 'Title' . $i . '=PVL! ' . $station['name'].': '.$stream['name'];
                        $output[] = 'Length' . $i . '=-1';
                        $output[] = 'Version=2';

                        $i++;
                    }
                }

                header('Content-Type: audio/x-scpls');
                header('Content-Disposition: attachment; filename="pvl_stations.pls"');
                echo implode("\r\n", $output);
            break;
        }
    }
}