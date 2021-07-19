<?php

declare(strict_types=1);

namespace App\Service;

use App\Environment;

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
        $environment = Environment::getInstance();

        if ($environment->isTesting()) {
            return false;
        }

        if ($environment->isDocker()) {
            return $environment->isDockerRevisionAtLeast(5);
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

        if ('Linux' === PHP_OS_FAMILY) {
            foreach (glob(' /etc/*-release', GLOB_NOSORT) ?: [] as $file) {
                $lines = array_filter(
                    array_map(
                        static function ($line) {
                            // split value from key
                            $parts = explode('=', $line);

                            // makes sure that "useless" lines are ignored (together with array_filter)
                            if (count($parts) !== 2) {
                                return false;
                            }

                            // remove quotes, if the value is quoted
                            $parts[1] = str_replace(['"', "'"], '', $parts[1]);
                            return $parts;
                        },
                        file($file) ?: []
                    )
                );

                foreach ($lines as $line) {
                    $vars[$line[0]] = trim($line[1]);
                }
            }
        }

        return $vars;
    }
}
