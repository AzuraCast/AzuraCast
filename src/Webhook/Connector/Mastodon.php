<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;
use App\Utilities\Urls;

/**
 * Mastodon web hook connector.
 */
final class Mastodon extends AbstractConnector
{
    public const NAME = 'mastodon';

    protected function getRateLimitTime(Entity\StationWebhook $webhook): ?int
    {
        $config = $webhook->getConfig();
        $rateLimitSeconds = (int)($config['rate_limit'] ?? 0);

        return max(10, $rateLimitSeconds);
    }

    public function dispatch(
        Entity\Station $station,
        Entity\StationWebhook $webhook,
        Entity\Api\NowPlaying\NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->getConfig();

        $instanceUrl = trim($config['instance_url'] ?? '');
        $accessToken = trim($config['access_token'] ?? '');

        if (empty($instanceUrl) || empty($accessToken)) {
            throw $this->incompleteConfigException();
        }

        $messages = $this->replaceVariables(
            [
                'message' => $config['message'] ?? '',
            ],
            $np
        );

        $instanceUri = Urls::parseUserUrl($instanceUrl, 'Mastodon Instance URL');
        $visibility = $config['visibility'] ?? 'public';

        $response = $this->httpClient->request(
            'POST',
            $instanceUri->withPath('/api/v1/statuses'),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'status' => $messages['message'],
                    'visibility' => $visibility,
                ],
            ]
        );

        $this->logger->debug(
            sprintf('Webhook %s returned code %d', self::NAME, $response->getStatusCode()),
            [
                'instanceUri' => (string)$instanceUri,
                'response' => $response->getBody()->getContents(),
            ]
        );
    }
}
