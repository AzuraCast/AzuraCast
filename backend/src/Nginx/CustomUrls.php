<?php

declare(strict_types=1);

namespace App\Nginx;

use App\Entity\Station;

final class CustomUrls
{
    public static function getListenUrl(Station $station): string
    {
        return '/listen/' . $station->short_name;
    }

    public static function getWebDjUrl(Station $station): string
    {
        return '/webdj/' . $station->short_name;
    }

    public static function getHlsUrl(Station $station): string
    {
        return '/hls/' . $station->short_name;
    }

    /**
     * Returns a custom path if X-Accel-Redirect is configured for the path provided.
     */
    public static function getXAccelPath(string $path): ?string
    {
        $specialPaths = [
            '/var/azuracast/stations' => '/internal/stations',
            '/var/azuracast/storage' => '/internal/storage',
            '/var/azuracast/backups' => '/internal/backups',
        ];

        foreach ($specialPaths as $diskPath => $nginxPath) {
            if (str_starts_with($path, $diskPath)) {
                return str_replace($diskPath, $nginxPath, $path);
            }
        }

        return null;
    }
}
