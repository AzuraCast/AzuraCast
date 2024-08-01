<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\MemoryStats\MemoryData;

final class MemoryStats
{
    public static function getMemoryUsage(): MemoryData
    {
        $meminfoRaw = file('/proc/meminfo', FILE_IGNORE_NEW_LINES) ?: [];
        $meminfo = [];

        foreach ($meminfoRaw as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$key, $val] = explode(':', $line);
            $meminfo[$key] = trim($val);
        }

        return MemoryData::fromMeminfo($meminfo);
    }
}
