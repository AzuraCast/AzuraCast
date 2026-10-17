<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\SettingsAwareTrait;
use App\Service\IpGeolocator\GeoLite;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MaxMind\Db\Reader;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class UpdateGeoLiteTask extends AbstractTask
{
    use SettingsAwareTrait;

    private const int UPDATE_THRESHOLD = 86000;

    private const string EDITION_ID = 'GeoLite2-City';

    public function __construct(
        private readonly Client $httpClient
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '42 */3 * * *';
    }

    public function run(bool $force = false): void
    {
        $settings = $this->readSettings();

        if (
            !$force
            && GeoLite::isAvailable()
            && GeoLite::getReader() === null
        ) {
            $this->logger->warning('Installed GeoLite database is invalid; updating regardless of last check.');
            $force = true;
        }

        if (!$force) {
            $lastRun = $settings->geolite_last_run;
            if ($lastRun > (time() - self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        try {
            $this->updateDatabase($settings->geolite_license_key ?? '');
        } catch (Exception $e) {
            $this->logger->error(
                'Error updating GeoLite database.',
                [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
        }

        $settings = $this->readSettings();
        $settings->updateGeoliteLastRun();
        $this->writeSettings($settings);
    }

    public function updateDatabase(string $licenseKey): void
    {
        if (empty($licenseKey)) {
            $this->logger->info('Not checking for GeoLite updates; no license key provided.');
            return;
        }

        $databasePath = GeoLite::getDatabasePath();

        $tmpDir = GeoLite::getBaseDirectory() . '/.update_' . bin2hex(random_bytes(8));

        $fsUtils = new Filesystem();
        $fsUtils->mkdir($tmpDir);

        try {
            $downloadPath = $tmpDir . '/geolite.tar.gz';

            set_time_limit(900);

            $this->httpClient->get(
                'https://download.maxmind.com/app/geoip_download',
                [
                    RequestOptions::HTTP_ERRORS => true,
                    RequestOptions::QUERY => [
                        'license_key' => $licenseKey,
                        'edition_id' => self::EDITION_ID,
                        'suffix' => 'tar.gz',
                    ],
                    RequestOptions::DECODE_CONTENT => false,
                    RequestOptions::SINK => $downloadPath,
                    RequestOptions::TIMEOUT => 600,
                ]
            );

            if (!is_file($downloadPath)) {
                throw new RuntimeException('New GeoLite database .tar.gz file not found.');
            }

            $process = new Process(
                [
                    'tar',
                    'xzf',
                    $downloadPath,
                    '--strip-components=1',
                ],
                $tmpDir
            );

            $process->mustRun();

            $newDatabasePath = $tmpDir . '/' . basename($databasePath);
            if (!is_file($newDatabasePath)) {
                throw new RuntimeException('New GeoLite database .mmdb file not found.');
            }

            $this->validateDatabase($newDatabasePath);

            if (!rename($newDatabasePath, $databasePath)) {
                throw new RuntimeException('Could not move new GeoLite database into place.');
            }
        } finally {
            $fsUtils->remove($tmpDir);
        }

        $newVersion = GeoLite::getVersion();
        $this->logger->info('GeoLite DB updated. New version: ' . $newVersion);
    }

    private function validateDatabase(string $path): void
    {
        $reader = new Reader($path);

        try {
            $databaseType = $reader->metadata()->databaseType;
            if ($databaseType !== self::EDITION_ID) {
                throw new RuntimeException(
                    sprintf('New GeoLite database has unexpected type "%s".', $databaseType)
                );
            }

            // Sample lookup to double check integrity
            $reader->get('8.8.8.8');
        } finally {
            $reader->close();
        }
    }
}
