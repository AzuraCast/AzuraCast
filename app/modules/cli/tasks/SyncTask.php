<?php
use \DF\Phalcon\Cli\Task;
use \PVL\SyncManager;

class SyncTask extends Task
{
    public function nowplayingAction($params = null) {
        if (isset($params[0]))
            define('NOWPLAYING_SEGMENT', $params[0]);

        SyncManager::syncNowplaying();
    }

    public function shortAction($params = null)
    {
        SyncManager::syncShort();
    }

    public function mediumAction($params = null)
    {
        SyncManager::syncMedium();
    }

    public function longAction($params = null)
    {
        SyncManager::syncLong();
    }
}