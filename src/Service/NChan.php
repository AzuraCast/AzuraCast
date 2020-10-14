<?php

namespace App\Service;

use App\Settings;

/**
 * Utility class for managing NChan, the nginx websocket/SSE/long-polling module.
 */
class NChan
{
    /**
     * @return bool Whether NChan is expected to be running on this installation.
     */
    public static function isSupported(): bool
    {
        $settings = Settings::getInstance();

        if ($settings->isTesting()) {
            return false;
        }

        if ($settings->isDocker()) {
            return $settings[Settings::DOCKER_REVISION] >= 5;
        }

        // Check for support for Ansible installations.
        $supportedCodenames = [
            'bionic',
            'focal',
        ];

        $os_details = self::getOperatingSystemDetails();
        return in_array($os_details['VERSION_CODENAME'], $supportedCodenames, true);
    }

    /**
     * Pull operating system details.
     * https://stackoverflow.com/questions/26862978/get-the-linux-distribution-name-in-php
     *
     * @return mixed[]
     */
    public static function getOperatingSystemDetails(): array
    {
        $vars = [];

        if (0 === stripos(PHP_OS, 'linux')) {
            $files = glob('/etc/*-release');

            foreach ($files as $file) {
                $lines = array_filter(array_map(function ($line) {
                    // split value from key
                    $parts = explode('=', $line);

                    // makes sure that "useless" lines are ignored (together with array_filter)
                    if (count($parts) !== 2) {
                        return false;
                    }

                    // remove quotes, if the value is quoted
                    $parts[1] = str_replace(['"', "'"], '', $parts[1]);
                    return $parts;
                }, file($file)));

                foreach ($lines as $line) {
                    $vars[$line[0]] = trim($line[1]);
                }
            }
        }

        return $vars;
    }
}
