<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Station;

class SftpGo
{
    public static function isSupportedForStation(Station $station): bool
    {
        return $station->getMediaStorageLocation()->isLocal();
    }
}
