<?php

declare(strict_types=1);

namespace App\Radio;

class PlaylistParser
{
    /**
     * @return string[]
     */
    public static function getSongs(string $playlistRaw): array
    {
        // Process as full PLS if the header is present.
        if (str_starts_with($playlistRaw, '[playlist]')) {
            $parsed_playlist = (array)parse_ini_string($playlistRaw, true, INI_SCANNER_RAW);

            return array_filter(
                $parsed_playlist['playlist'],
                static function ($key) {
                    return str_starts_with(strtolower($key), 'file');
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        $filter_line = static function ($line) {
            return trim($line);
        };

        // Process as a simple list of files or M3U-style playlist.
        $lines = explode("\n", $playlistRaw);
        return array_filter(
            array_map($filter_line, $lines),
            static function ($line) {
                return !empty($line) && $line[0] !== '#';
            }
        );
    }
}
