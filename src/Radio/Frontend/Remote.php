<?php
namespace App\Radio\Frontend;

use App\Entity;

class Remote extends FrontendAbstract
{
    public function read(Entity\Station $station): bool
    {
        return true;
    }

    public function write(Entity\Station $station): bool
    {
        return true;
    }

    public function isRunning(Entity\Station $station): bool
    {
        return true;
    }

    public function getStreamUrl(Entity\Station $station): string
    {
        return '';
    }

    public function getStreamUrls(Entity\Station $station): array
    {
        return [];
    }

    public function getAdminUrl(Entity\Station $station): string
    {
        return '';
    }

    public static function supportsMounts(): bool
    {
        return false;
    }

    public static function supportsListenerDetail(): bool
    {
        return false;
    }
}
