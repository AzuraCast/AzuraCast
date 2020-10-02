<?php
namespace App\Service;

use App\Entity;
use App\Settings;
use App\Version;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class AzuraCastCentral
{
    protected const BASE_URL = 'https://central.azuracast.com';

    protected Settings $app_settings;

    protected Client $http_client;

    protected Entity\Repository\SettingsRepository $settings_repo;

    protected Version $version;

    protected LoggerInterface $logger;

    public function __construct(
        Entity\Repository\SettingsRepository $settings_repo,
        Settings $app_settings,
        Version $version,
        Client $http_client,
        LoggerInterface $logger
    ) {
        $this->settings_repo = $settings_repo;
        $this->app_settings = $app_settings;
        $this->version = $version;
        $this->http_client = $http_client;
        $this->logger = $logger;
    }

    /**
     * Ping the AzuraCast Central server for updates and return them if there are any.
     *
     * @return array|null
     */
    public function checkForUpdates(): ?array
    {
        $app_uuid = $this->settings_repo->getUniqueIdentifier();

        $request_body = [
            'id' => $app_uuid,
            'is_docker' => $this->app_settings->isDocker(),
            'environment' => $this->app_settings[Settings::APP_ENV],
        ];

        $commit_hash = $this->version->getCommitHash();
        if ($commit_hash) {
            $request_body['version'] = $commit_hash;
        } else {
            $request_body['release'] = Version::FALLBACK_VERSION;
        }

        try {
            $response = $this->http_client->request(
                'POST',
                self::BASE_URL . '/api/update',
                ['json' => $request_body]
            );

            $update_data_raw = $response->getBody()->getContents();

            $update_data = json_decode($update_data_raw, true, 512, JSON_THROW_ON_ERROR);
            return $update_data['updates'] ?? null;
        } catch (Exception $e) {
            $this->logger->error('Error checking for updates: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Ping the AzuraCast Central server to retrieve this installation's likely public-facing IP.
     *
     * @param bool $cached
     *
     * @return string|null
     */
    public function getIp(bool $cached = true): ?string
    {
        $ip = ($cached)
            ? $this->settings_repo->getSetting(Entity\Settings::EXTERNAL_IP)
            : null;

        if (empty($ip)) {
            try {
                $response = $this->http_client->request(
                    'GET',
                    self::BASE_URL . '/ip'
                );

                $body_raw = $response->getBody()->getContents();
                $body = json_decode($body_raw, true, 512, JSON_THROW_ON_ERROR);

                $ip = $body['ip'] ?? null;
            } catch (Exception $e) {
                $this->logger->error('Could not fetch remote IP: ' . $e->getMessage());
                $ip = null;
            }

            if (!empty($ip) && $cached) {
                $this->settings_repo->setSetting(Entity\Settings::EXTERNAL_IP, $ip);
            }
        }

        return $ip;
    }
}
