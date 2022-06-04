<?php

declare(strict_types=1);

namespace App\Nginx;

use App\Entity\Station;

final class CustomUrls
{
    public static function getListenUrl(Station $station): string
    {
        return '/listen/' . $station->getShortName();
    }

    public static function getWebDjUrl(Station $station): string
    {
        return '/webdj/' . $station->getShortName();
    }

    public static function getHlsUrl(Station $station): string
    {
        return '/hls/' . $station->getShortName();
    }

    /**
     * Returns a custom path if X-Accel-Redirect is configured for the path provided.
     */
    public static function getXAccelPath(string $path): ?string
    {
        $specialPaths = [
            '/var/azuracast/backups' => '/internal/backups',
            '/var/azuracast/stations' => '/internal/stations',
        ];

        foreach ($specialPaths as $diskPath => $nginxPath) {
            if (str_starts_with($path, $diskPath)) {
                return str_replace($diskPath, $nginxPath, $path);
            }
        }

        return null;
    }
}
