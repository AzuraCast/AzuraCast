<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\SettingsAwareTrait;
use App\Service\IpGeolocator\GeoLite;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use RuntimeException;
use Symfony\Component\Process\Process;

final class UpdateGeoLiteTask extends AbstractTask
{
    use SettingsAwareTrait;

    private const UPDATE_THRESHOLD = 86000;

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

        if (!$force) {
            $lastRun = $settings->getGeoliteLastRun();
            if ($lastRun > (time() - self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        try {
            $this->updateDatabase($settings->getGeoliteLicenseKey() ?? '');
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

        $baseDir = GeoLite::getBaseDirectory();
        $downloadPath = $baseDir . '/geolite.tar.gz';

        set_time_limit(900);

        $this->httpClient->get(
            'https://download.maxmind.com/app/geoip_download',
            [
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::QUERY => [
                    'license_key' => $licenseKey,
                    'edition_id' => 'GeoLite2-City',
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
                'xvzf',
                $downloadPath,
                '--strip-components=1',
            ],
            $baseDir
        );

        $process->mustRun();

        unlink($downloadPath);

        $newVersion = GeoLite::getVersion();
        $this->logger->info('GeoLite DB updated. New version: ' . $newVersion);
    }
}
