<?php
use \App\Phalcon\Cli\Task;
use \App\Sync\Manager;

class SyncTask extends Task
{
    public function nowplayingAction($segment = 1)
    {
        define('NOWPLAYING_SEGMENT', $segment);
        Manager::syncNowplaying();
    }

    public function shortAction($params = null)
    {
        Manager::syncShort();
    }

    public function mediumAction($params = null)
    {
        Manager::syncMedium();
    }

    public function longAction($params = null)
    {
        Manager::syncLong();
    }
}