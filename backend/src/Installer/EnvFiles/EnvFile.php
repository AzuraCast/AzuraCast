<?php

declare(strict_types=1);

namespace App\Installer\EnvFiles;

use App\Environment;
use App\Radio\Configuration;

use function __;

final class EnvFile extends AbstractEnvFile
{
    /** @inheritDoc */
    public static function getConfiguration(Environment $environment): array
    {
        static $config = null;

        if (null === $config) {
            $config = [
                'COMPOSE_PROJECT_NAME' => [
                    'name' => __(
                        '(Docker Compose) All Docker containers are prefixed by this name. Do not change this after installation.'
                    ),
                    'default' => 'azuracast',
                    'required' => true,
                ],
                'COMPOSE_HTTP_TIMEOUT' => [
                    'name' => __(
                        '(Docker Compose) The amount of time to wait before a Docker Compose operation fails. Increase this on lower performance computers.'
                    ),
                    'default' => 300,
                    'required' => true,
                ],
                'AZURACAST_VERSION' => [
                    'name' => __('Release Channel'),
                    'options' => ['latest', 'stable'],
                    'default' => 'latest',
                    'required' => true,
                ],
                'AZURACAST_HTTP_PORT' => [
                    'name' => __('HTTP Port'),
                    'description' => __(
                        'The main port AzuraCast listens to for insecure HTTP connections.',
                    ),
                    'default' => 80,
                ],
                'AZURACAST_HTTPS_PORT' => [
                    'name' => __('HTTPS Port'),
                    'description' => __(
                        'The main port AzuraCast listens to for secure HTTPS connections.',
                    ),
                    'default' => 443,
                ],
                'AZURACAST_SFTP_PORT' => [
                    'name' => __('SFTP Port'),
                    'description' => __(
                        'The port AzuraCast listens to for SFTP file management connections.',
                    ),
                    'default' => 2022,
                ],
                'AZURACAST_STATION_PORTS' => [
                    'name' => __('Station Ports'),
                    'description' => __(
                        'The ports AzuraCast should listen to for station broadcasts and incoming DJ connections.',
                    ),
                    'default' => implode(',', Configuration::enumerateDefaultPorts()),
                ],
                'AZURACAST_PUID' => [
                    'name' => __('Docker User UID'),
                    'description' => __(
                        'Set the UID of the user running inside the Docker containers. Matching this with your host UID can fix permission issues.',
                    ),
                    'default' => 1000,
                ],
                'AZURACAST_PGID' => [
                    'name' => __('Docker User GID'),
                    'description' => __(
                        'Set the GID of the user running inside the Docker containers. Matching this with your host GID can fix permission issues.'
                    ),
                    'default' => 1000,
                ],
                'AZURACAST_PODMAN_MODE' => [
                    'name' => __('Use Podman instead of Docker.'),
                    'default' => false,
                ],
                'AZURACAST_COMPOSE_PRIVILEGED' => [
                    'name' => __('Advanced: Use Privileged Docker Settings'),
                    'default' => true,
                ],
            ];
        }

        return $config;
    }

    public static function buildPathFromBase(string $baseDir): string
    {
        return $baseDir . DIRECTORY_SEPARATOR . '.env';
    }
}
