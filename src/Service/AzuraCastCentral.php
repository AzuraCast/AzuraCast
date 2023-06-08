<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Version;
use Exception;
use GuzzleHttp\Client;

final class AzuraCastCentral
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    private const BASE_URL = 'https://central.azuracast.com';

    public function __construct(
        private readonly Version $version,
        private readonly Client $httpClient,
    ) {
    }

    /**
     * Ping the AzuraCast Central server for updates and return them if there are any.
     *
     * @return mixed[]|null
     */
    public function checkForUpdates(): ?array
    {
        $requestBody = [
            'id' => $this->getUniqueIdentifier(),
            'is_docker' => $this->environment->isDocker(),
            'environment' => $this->environment->getAppEnvironmentEnum()->value,
            'release_channel' => $this->version->getReleaseChannelEnum()->value,
        ];

        $commitHash = $this->version->getCommitHash();
        if ($commitHash) {
            $requestBody['version'] = $commitHash;
        } else {
            $requestBody['release'] = Version::FALLBACK_VERSION;
        }

        $this->logger->debug(
            'Update request body',
            [
                'body' => $requestBody,
            ]
        );

        try {
            $response = $this->httpClient->request(
                'POST',
                self::BASE_URL . '/api/update',
                ['json' => $requestBody]
            );

            $updateDataRaw = $response->getBody()->getContents();

            $updateData = json_decode($updateDataRaw, true, 512, JSON_THROW_ON_ERROR);
            return $updateData['updates'] ?? null;
        } catch (Exception $e) {
            $this->logger->error('Error checking for updates: ' . $e->getMessage());
        }

        return null;
    }

    public function getUniqueIdentifier(): string
    {
        return $this->readSettings()->getAppUniqueIdentifier();
    }

    /**
     * Ping the AzuraCast Central server to retrieve this installation's likely public-facing IP.
     *
     * @param bool $cached
     */
    public function getIp(bool $cached = true): ?string
    {
        $settings = $this->readSettings();
        $ip = ($cached)
            ? $settings->getExternalIp()
            : null;

        if (empty($ip)) {
            try {
                $response = $this->httpClient->request(
                    'GET',
                    self::BASE_URL . '/ip'
                );

                $bodyRaw = $response->getBody()->getContents();
                $body = json_decode($bodyRaw, true, 512, JSON_THROW_ON_ERROR);

                $ip = $body['ip'] ?? null;
            } catch (Exception $e) {
                $this->logger->error('Could not fetch remote IP: ' . $e->getMessage());
                $ip = null;
            }

            if (!empty($ip) && $cached) {
                $settings->setExternalIp($ip);
                $this->writeSettings($settings);
            }
        }

        return $ip;
    }
}
