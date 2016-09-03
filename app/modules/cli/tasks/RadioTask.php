<?php
use \App\Phalcon\Cli\Task;

class RadioTask extends Task
{
    /**
     * Restart all radio stations across the system.
     */
    public function restartAction()
    {
        \App\Debug::setEchoMode(true);

        \App\Debug::log('Restarting all radio stations...');
        \App\Debug::divider();

        $stations = \Entity\Station::fetchAll();

        foreach($stations as $station)
        {
            \App\Debug::log('Restarting station #'.$station->id.': '.$station->name);

            $backend = $station->getBackendAdapter();
            $frontend = $station->getFrontendAdapter();

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