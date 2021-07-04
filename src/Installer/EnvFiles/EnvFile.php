<?php

namespace App\Installer\EnvFiles;

use App\Radio\Configuration;

use function __;

class EnvFile extends AbstractEnvFile
{
    /** @inheritDoc */
    public static function getConfiguration(): array
    {
        return [
            'COMPOSE_PROJECT_NAME' => [
                'name' => __(
                    '(Docker Compose) All Docker containers are prefixed by this name. Do not change this after installation.'
                ),
                'default' => 'azuracast',
            ],
            'COMPOSE_HTTP_TIMEOUT' => [
                'name' => __(
                    '(Docker Compose) The amount of time to wait before a Docker Compose operation fails. Increase this on lower performance computers.'
                ),
                'default' => 300,
            ],
            'AZURACAST_VERSION' => [
                'name' => __('AzuraCast Release Channel'),
                'options' => ['latest', 'stable'],
                'default' => 'latest',
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
            'LETSENCRYPT_HOST' => [
                'name' => __('LetsEncrypt Domain Name(s)'),
                'description' => __(
                    'Domain name (example.com) or names (example.com,foo.bar) to use with LetsEncrypt.'
                ),
            ],
            'LETSENCRYPT_EMAIL' => [
                'name' => __('LetsEncrypt E-mail Address'),
                'description' => __(
                    'Optionally provide an e-mail address for updates from LetsEncrypt.',
                ),
            ],
        ];
    }

    public static function buildPathFromBase(string $baseDir): string
    {
        return $baseDir . DIRECTORY_SEPARATOR . '.env';
    }
}
