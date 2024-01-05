<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Utilities\Types;
use App\Utilities\Urls;

/**
 * Mastodon web hook connector.
 */
final class Mastodon extends AbstractSocialConnector
{
    protected function getRateLimitTime(StationWebhook $webhook): ?int
    {
        $config = $webhook->getConfig();
        $rateLimitSeconds = (int)($config['rate_limit'] ?? 0);

        return max(10, $rateLimitSeconds);
    }

    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->getConfig();

        $instanceUrl = Types::stringOrNull($config['instance_url'], true);
        $accessToken = Types::stringOrNull($config['access_token'], true);

        if (null === $instanceUrl || null === $accessToken) {
            throw $this->incompleteConfigException($webhook);
        }

        $instanceUri = Urls::parseUserUrl($instanceUrl, 'Mastodon Instance URL');
        $visibility = Types::stringOrNull($config['visibility'], true) ?? 'public';

        $this->logger->debug(
            'Posting to Mastodon...',
            [
                'url' => (string)$instanceUri,
            ]
        );

        foreach ($this->getMessages($webhook, $np, $triggers) as $message) {
            $messageBody = [
                'status' => $message,
                'visibility' => $visibility,
            ];

            $response = $this->httpClient->request(
                'POST',
                $instanceUri->withPath('/api/v1/statuses'),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $messageBody,
                ]
            );

            $this->logHttpResponse(
                $webhook,
                $response,
                $messageBody
            );
        }
    }
}
