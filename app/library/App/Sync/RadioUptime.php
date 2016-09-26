<?php
namespace App\Sync;

use Entity\Station;
use Entity\StationMedia;
use Entity\StationPlaylist;

class RadioUptime extends SyncAbstract
{
    public function run()
    {
        \App\Debug::setEchoMode(true);

        \App\Debug::log('Checking all stations for active running status...');
        \App\Debug::divider();

        $stations = \Entity\Station::fetchAll();

        foreach($stations as $station)
        {
            $backend = $station->getBackendAdapter();
            $frontend = $station->getFrontendAdapter();

            if (!$backend->isRunning() || !$frontend->isRunning())
            {
                \App\Debug::log('Restarting station #'.$station->id.': '.$station->name);

                $backend->stop();
                $frontend->stop();

                $frontend->write();
                $backend->write();

                $frontend->start();
                $backend->start();

                \App\Debug::divider();
            }
        }
    }
}