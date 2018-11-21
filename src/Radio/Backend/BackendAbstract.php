<?php
namespace App\Radio\Backend;

use App\Entity;

abstract class BackendAbstract extends \App\Radio\AdapterAbstract
{
    public function getStreamPort(Entity\Station $station)
    {
        return null;
    }

    public function getProgramName(Entity\Station $station): string
    {
        return 'station_' . $station->getId() . ':station_' . $station->getId() . '_backend';
    }

    public static function supportsMedia(): bool
    {
        return true;
    }

    public static function supportsRequests(): bool
    {
        return true;
    }

    public static function supportsStreamers(): bool
    {
        return true;
    }

    public static function supportsWebStreaming(): bool
    {
        return true;
    }
}
