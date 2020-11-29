<?php

namespace App\Radio;

class PlaylistParser
{
    /**
     * @return string[]
     */
    public static function getSongs($playlist_raw): array
    {
        // Process as full PLS if the header is present.
        if (strpos($playlist_raw, '[playlist]') === 0) {
            $parsed_playlist = (array)parse_ini_string($playlist_raw, true, INI_SCANNER_RAW);

            $paths = [];
            foreach ($parsed_playlist['playlist'] as $playlist_key => $playlist_line) {
                if (strpos(strtolower($playlist_key), 'file') === 0) {
                    $paths[] = $playlist_line;
                }
            }
        } else {
            $filter_line = function ($line) {
                return trim(urldecode($line));
            };

            // Process as a simple list of files or M3U-style playlist.
            $lines = explode("\n", $playlist_raw);
            $paths = array_filter(array_map($filter_line, $lines), function ($line) {
                return !empty($line) && $line[0] !== '#';
            });
        }

        return $paths;
    }
}
