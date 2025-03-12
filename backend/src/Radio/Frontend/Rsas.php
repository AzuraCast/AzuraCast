<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Entity\Api\LogType;
use App\Entity\Station;
use App\Entity\StationMount;
use App\Environment;
use App\Radio\Enums\StreamFormats;
use App\Service\Acme;
use App\Utilities\Arrays;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use Throwable;

final class Rsas extends Icecast
{
    public function getBinary(): ?string
    {
        $binaryPath = self::getDirectory() . '/rsas';
        return file_exists($binaryPath)
            ? $binaryPath
            : null;
    }

    public static function getDirectory(): string
    {
        return Environment::getInstance()->getParentDirectory() . '/storage/rsas';
    }

    public static function getLicensePath(): string
    {
        return self::getDirectory() . '/license.key';
    }

    public function hasLicense(): bool
    {
        return file_exists(self::getLicensePath());
    }

    public function getConfigurationPath(Station $station): string
    {
        return $station->getRadioConfigDir() . '/rsas.xml';
    }

    public function getVersion(): ?string
    {
        $binary = $this->getBinary();
        if (!$binary) {
            return null;
        }

        $process = new Process([$binary, '--version']);
        $process->setWorkingDirectory(dirname($binary));
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        return preg_match('/^Rocket Streaming Audio Server ([.\d]+) .*$/i', $process->getOutput(), $matches)
            ? $matches[1]
            : null;
    }

    public function write(Station $station): bool
    {
        $parentChanged = parent::write($station);

        // Copy the license.key (if it exists) to the station config dir.
        $fsUtils = new Filesystem();

        $stationLicenseKeyPath = $station->getRadioConfigDir() . '/license.key';

        try {
            $currentLicense = $fsUtils->readFile($stationLicenseKeyPath);
        } catch (Throwable) {
            $currentLicense = '';
        }

        if ($this->hasLicense()) {
            $globalLicenseKeyPath = self::getLicensePath();

            $fsUtils->copy(
                $globalLicenseKeyPath,
                $stationLicenseKeyPath,
                true
            );

            try {
                $newLicense = $fsUtils->readFile($stationLicenseKeyPath);
            } catch (Throwable) {
                $newLicense = '';
            }
        } else {
            $fsUtils->remove($stationLicenseKeyPath);
            $newLicense = '';
        }

        $licenseChanged = 0 !== strcmp($currentLicense, $newLicense);
        return $parentChanged || $licenseChanged;
    }

    protected function getConfigurationArray(Station $station): array
    {
        $frontendConfig = $station->getFrontendConfig();
        $configDir = $station->getRadioConfigDir();

        $settingsBaseUrl = $this->settingsRepo->readSettings()->getBaseUrlAsUri();
        $baseUrl = $settingsBaseUrl ?? new Uri('http://localhost');

        [$certPath, $certKey] = Acme::getCertificatePaths();

        $config = [
            'hostname' => $baseUrl->getHost(),
            'listen-socket' => [
                'port' => $frontendConfig->getPort(),
                'bind-address' => '0.0.0.0',
                // 'tls' => 1,
            ],
            'emulation' => [
                'icecast-status-page' => 1,
            ],
            'authentication' => [
                'admin-password' => $frontendConfig->getAdminPassword(),
            ],
            'limits' => [
                'workers' => 'auto',
                'clients' => $frontendConfig->getMaxListeners() ?? 2500,
            ],
            'paths' => [
                'logdir' => $configDir,
                'webroot' => self::WEBROOT,
                'ssl-private-key' => $certKey,
                'ssl-certificate' => $certPath,
                // phpcs:disable Generic.Files.LineLength
                'ssl-allowed-ciphers' => 'ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS',
                // phpcs:enable
            ],
            'logging' => [
                'access' => [
                    'filename' => 'rsas_access.log',
                    'maxlogs' => 2,
                ],
                'error' => [
                    'filename' => 'rsas_error.log',
                    'maxlogs' => 2,
                ],
                'playlist' => [
                    'filename' => 'rsas_playlist.log',
                    'maxlogs' => 2,
                ],
            ],
        ];

        $bannedCountries = $frontendConfig->getBannedCountries() ?? [];
        $allowedIps = $this->getIpsAsArray($frontendConfig->getAllowedIps());
        $bannedIps = $this->getIpsAsArray($frontendConfig->getBannedIps());
        $bannedUserAgents = $frontendConfig->getBannedUserAgents();

        $useListenerAuth = !empty($bannedCountries) || !empty($allowedIps)
            || !empty($bannedIps) || !empty($bannedUserAgents);

        /** @var StationMount $mountRow */
        foreach ($station->getMounts() as $mountRow) {
            $mount = [
                'mount-name' => $mountRow->getName(),
                'username' => 'source',
                'password' => $frontendConfig->getSourcePassword(),
            ];

            if ($mountRow->getMaxListenerDuration()) {
                $mount['max-listener-duration'] = $mountRow->getMaxListenerDuration();
            }

            if (!$mountRow->getIsVisibleOnPublicPages()) {
                $mount['hidden'] = 1;
            }

            if (!empty($mountRow->getIntroPath())) {
                $introPath = $mountRow->getIntroPath();
                // The intro path is appended to webroot, so the path should be relative to it.
                $mount['preroll'] = Path::makeRelative(
                    $station->getRadioConfigDir() . '/' . $introPath,
                    self::WEBROOT
                );
            }

            if (!empty($mountRow->getFallbackMount())) {
                $mount['fallback-mount'] = $mountRow->getFallbackMount();
                $mount['fallback-override'] = 1;
            } elseif ($mountRow->getEnableAutodj()) {
                $autoDjFormat = $mountRow->getAutodjFormat() ?? StreamFormats::default();
                $autoDjBitrate = $mountRow->getAutodjBitrate();

                $mount['fallback-mount'] = '/fallback-[' . $autoDjBitrate . '].' . $autoDjFormat->getExtension();
                $mount['fallback-override'] = 1;
            }

            $mountFrontendConfig = trim($mountRow->getFrontendConfig() ?? '');
            if (!empty($mountFrontendConfig)) {
                $mountConf = $this->processCustomConfig($mountFrontendConfig);
                if (false !== $mountConf) {
                    $mount = Arrays::arrayMergeRecursiveDistinct($mount, $mountConf);
                }
            }

            $mountRelayUri = $mountRow->getRelayUrlAsUri();
            if (null !== $mountRelayUri) {
                $config['relay'][] = array_filter([
                    'server' => $mountRelayUri->getHost(),
                    'port' => $mountRelayUri->getPort(),
                    'mount' => $mountRelayUri->getPath(),
                    'local-mount' => $mountRow->getName(),
                ]);
            }

            if ($useListenerAuth) {
                $mount['authentication'][] = [
                    '@type' => 'url',
                    'option' => [
                        [
                            '@name' => 'listener_add',
                            '@value' => $this->getAuthenticationUrl($station),
                        ],
                        [
                            '@name' => 'auth_header',
                            '@value' => 'icecast-auth-user: 1',
                        ],
                    ],
                ];
            }

            $config['mount'][] = $mount;
        }

        $customConfParsed = $this->processCustomConfig($frontendConfig->getCustomConfiguration());
        if (false !== $customConfParsed) {
            $config = Arrays::arrayMergeRecursiveDistinct($config, $customConfParsed);
        }

        return $config;
    }

    public function getLogTypes(Station $station): array
    {
        $stationConfigDir = $station->getRadioConfigDir();

        return [
            new LogType(
                'rsas_access_log',
                __('RSAS Access Log'),
                $stationConfigDir . '/rsas_access.log',
                true
            ),
            new LogType(
                'rsas_error_log',
                __('RSAS Error Log'),
                $stationConfigDir . '/rsas_error.log',
                true
            ),
            new LogType(
                'rsas_xml',
                __('RSAS Configuration'),
                $stationConfigDir . '/rsas.xml',
                false
            ),
        ];
    }
}
