<?php

declare(strict_types=1);

namespace App\Radio;

final class PlaylistParser
{
    /**
     * @return string[]
     */
    public static function getSongs(string $playlistRaw): array
    {
        // Process as full PLS if the header is present.
        if (str_starts_with($playlistRaw, '[playlist]')) {
            $parsedPlaylist = (array)parse_ini_string($playlistRaw, true, INI_SCANNER_RAW);

            return array_filter(
                $parsedPlaylist['playlist'],
                static function ($key) {
                    return str_starts_with(strtolower($key), 'file');
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        // Process as a simple list of files or M3U-style playlist.
        $lines = preg_split(
            "/[\r\n]+/",        // regex supports Windows, Linux/Unix & Old Macs EOL's
            $playlistRaw,
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        if (false === $lines) {
            return [];
        }
        return array_filter(
            array_map('trim', $lines),
            static function ($line) {
                return $line[0] !== '#';
            }
        );
    }
}
