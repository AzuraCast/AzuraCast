<?php

namespace App\Entity;

interface ProcessableMediaInterface
{
    public static function needsReprocessing(
        int $fileModifiedTime = 0,
        int $dbModifiedTime = 0
    ): bool;
}
