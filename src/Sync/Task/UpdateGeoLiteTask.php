<?php

namespace App\Sync\Task;

use App\Entity;
use App\Service\IpGeolocation;
use App\Service\IpGeolocator\GeoLite;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

class UpdateGeoLiteTask extends AbstractTask
{
    protected const UPDATE_THRESHOLD = 86000;

    protected Client $httpClient;

    protected IpGeolocation $geoLite;

    protected Entity\Repository\SettingsTableRepository $settingsTableRepo;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Entity\Settings $settings,
        Client $httpClient,
        IpGeolocation $geoLite,
        Entity\Repository\SettingsTableRepository $settingsTableRepo
    ) {
        parent::__construct($em, $logger, $settings);

        $this->httpClient = $httpClient;
        $this->geoLite = $geoLite;
        $this->settingsTableRepo = $settingsTableRepo;
    }

    public function run(bool $force = false): void
    {
        if (!$force) {
            $lastRun = $this->settings->getGeoliteLastRun();

            if ($lastRun > (time() - self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        try {
            $this->updateDatabase();
        } catch (Exception $e) {
            $this->logger->error('Error updating GeoLite database.', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        $this->settings->updateGeoliteLastRun();
        $this->settingsTableRepo->writeSettings($this->settings);
    }

    public function updateDatabase(): void
    {
        $licenseKey = trim($this->settings->getGeoliteLicenseKey());

        if (empty($licenseKey)) {
            $this->logger->info('Not checking for GeoLite updates; no license key provided.');
            return;
        }

        $baseDir = GeoLite::getBaseDirectory();
        $downloadPath = $baseDir . '/geolite.tar.gz';

        set_time_limit(900);

        $this->httpClient->get('https://download.maxmind.com/app/geoip_download', [
            RequestOptions::HTTP_ERRORS => true,
            RequestOptions::QUERY => [
                'license_key' => $licenseKey,
                'edition_id' => 'GeoLite2-City',
                'suffix' => 'tar.gz',
            ],
            RequestOptions::DECODE_CONTENT => false,
            RequestOptions::SINK => $downloadPath,
            RequestOptions::TIMEOUT => 600,
        ]);

        if (!file_exists($downloadPath)) {
            throw new RuntimeException('New GeoLite database .tar.gz file not found.');
        }

        $process = new Process([
            'tar',
            'xvzf',
            $downloadPath,
            '--strip-components=1',
        ], $baseDir);

        $process->mustRun();

        unlink($downloadPath);

        $newVersion = GeoLite::getVersion();
        $this->logger->info('GeoLite DB updated. New version: ' . $newVersion);
    }
}
