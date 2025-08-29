<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Api\Admin\UpdateDetails;
use App\Version;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use RuntimeException;

final class AzuraCastCentral
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    private const string BASE_URL = 'https://central.azuracast.com';

    public function __construct(
        private readonly Version $version,
        private readonly Client $httpClient,
    ) {
    }

    /**
     * Ping the AzuraCast Central server for updates and return them if there are any.
     */
    public function checkForUpdates(): UpdateDetails
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
            $requestBody['release'] = Version::STABLE_VERSION;
        }

        $this->logger->debug(
            'Update request body',
            [
                'body' => $requestBody,
            ]
        );

        $response = $this->httpClient->request(
            'POST',
            self::BASE_URL . '/api/update',
            [
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::JSON => $requestBody,
                RequestOptions::TIMEOUT => 15,
            ]
        );

        $updateDataRaw = $response->getBody()->getContents();

        $this->logger->debug('Update response body.', [
            'response' => $updateDataRaw,
        ]);

        $updateData = json_decode($updateDataRaw, true, 512, JSON_THROW_ON_ERROR);
        $updates = $updateData['updates'] ?? null;

        if (empty($updates)) {
            throw new RuntimeException('Central server did not send update information.');
        }

        return UpdateDetails::fromArray($updates);
    }

    public function getUniqueIdentifier(): string
    {
        return $this->readSettings()->app_unique_identifier;
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
            ? $settings->external_ip
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
                $settings->external_ip = $ip;
                $this->writeSettings($settings);
            }
        }

        return $ip;
    }
}
