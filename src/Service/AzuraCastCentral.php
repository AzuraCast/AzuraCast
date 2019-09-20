<?php
namespace App\Service;

use App\Entity;
use App\Settings;
use App\Version;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;

class AzuraCastCentral
{
    protected const BASE_URL = 'https://central.azuracast.com';

    /** @var Settings */
    protected $app_settings;

    /** @var Client */
    protected $http_client;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Version */
    protected $version;

    /**
     * @param EntityManager $em
     * @param Settings $app_settings
     * @param Version $version
     * @param Client $http_client
     */
    public function __construct(
        EntityManager $em,
        Settings $app_settings,
        Version $version,
        Client $http_client
    ) {
        $this->settings_repo = $em->getRepository(Entity\Settings::class);
        $this->app_settings = $app_settings;
        $this->version = $version;
        $this->http_client = $http_client;
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
            'is_docker' => (bool)$this->app_settings->isDocker(),
            'environment' => $this->app_settings[Settings::APP_ENV],
        ];

        $commit_hash = $this->version->getCommitHash();
        if ($commit_hash) {
            $request_body['version'] = $commit_hash;
        } else {
            $request_body['release'] = Version::FALLBACK_VERSION;
        }

        $response = $this->http_client->request(
            'POST',
            self::BASE_URL . '/api/update',
            ['json' => $request_body]
        );

        $update_data_raw = $response->getBody()->getContents();

        $update_data = json_decode($update_data_raw, true);
        return $update_data['updates'] ?? null;
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
            $response = $this->http_client->request(
                'GET',
                self::BASE_URL . '/ip'
            );

            $body_raw = $response->getBody()->getContents();
            $body = json_decode($body_raw, true);

            $ip = $body['ip'] ?? null;

            if (!empty($ip) && $cached) {
                $this->settings_repo->setSetting(Entity\Settings::EXTERNAL_IP, $ip);
            }
        }

        return $ip;
    }
}
