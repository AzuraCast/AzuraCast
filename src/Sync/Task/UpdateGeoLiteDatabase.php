<?php
namespace App\Sync\Task;

use App\Entity;
use App\Service\GeoLite;
use Azura\Logger;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Process\Process;

class UpdateGeoLiteDatabase extends AbstractTask
{
    protected const UPDATE_THRESHOLD = 86000;

    protected Client $httpClient;

    protected GeoLite $geoLite;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Client $httpClient,
        GeoLite $geoLite
    ) {
        parent::__construct($em, $settingsRepo);

        $this->httpClient = $httpClient;
        $this->geoLite = $geoLite;
    }

    public function run($force = false): void
    {
        $logger = Logger::getInstance();

        if (!$force) {
            $lastRun = (int)$this->settingsRepo->getSetting(Entity\Settings::GEOLITE_LAST_RUN, 0);

            if ($lastRun > (time() - self::UPDATE_THRESHOLD)) {
                $logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        $licenseKey = trim($this->settingsRepo->getSetting(Entity\Settings::GEOLITE_LICENSE_KEY));

        if (!empty($licenseKey)) {
            $baseDir = dirname($this->geoLite->getDatabasePath());
            $downloadPath = $baseDir . '/geolite.tar.gz';

            try {
                set_time_limit(900);

                $this->httpClient->get('https://download.maxmind.com/app/geoip_download', [
                    'query' => [
                        'license_key' => $licenseKey,
                        'edition_id' => 'GeoLite2-City',
                        'suffix' => 'tar.gz',
                    ],
                    'decode_content' => false,
                    'sink' => $downloadPath,
                    'timeout' => 600,
                ]);
            } catch (ClientException $e) {
                $logger->error('Error downloading GeoLite database: ' . $e->getMessage());
            }

            if (file_exists($downloadPath)) {
                $process = new Process([
                    'tar',
                    'xvzf',
                    $downloadPath,
                    '--strip-components=1',
                ], $baseDir);

                $process->mustRun();

                unlink($downloadPath);

                $newVersion = $this->geoLite->getVersion();
                $logger->info('GeoLite DB updated. New version: ' . $newVersion);
            } else {
                $logger->error('Could not download updated GeoLite database.');
            }
        } else {
            $logger->info('Not checking for GeoLite updates; no license key provided.');
        }

        $this->settingsRepo->setSetting(Entity\Settings::GEOLITE_LAST_RUN, time());
    }
}


