<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Enums\WebhookTriggers;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Utilities\Urls;

/**
 * Mastodon web hook connector.
 */
final class Mastodon extends AbstractConnector
{
    public const NAME = 'mastodon';

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

        $instanceUrl = trim($config['instance_url'] ?? '');
        $accessToken = trim($config['access_token'] ?? '');

        if (empty($instanceUrl) || empty($accessToken)) {
            throw $this->incompleteConfigException(self::NAME);
        }

        $instanceUri = Urls::parseUserUrl($instanceUrl, 'Mastodon Instance URL');
        $visibility = $config['visibility'] ?? 'public';

        $messages = [
            WebhookTriggers::SongChanged->value => $config['message'] ?? '',
            WebhookTriggers::LiveConnect->value => $config['message_live_connect'] ?? '',
            WebhookTriggers::LiveDisconnect->value => $config['message_live_disconnect'] ?? '',
            WebhookTriggers::StationOffline->value => $config['message_station_offline'] ?? '',
            WebhookTriggers::StationOnline->value => $config['message_station_online'] ?? '',
        ];

        foreach ($triggers as $trigger) {
            $message = $messages[$trigger] ?? '';
            if (empty($message)) {
                $this->logger->error(
                    'Cannot send Twitter message; message body for this trigger type is empty.'
                );
                return;
            }

            $vars = $this->replaceVariables(['message' => $message], $np);

            $this->logger->debug('Posting to Twitter...');

            $response = $this->httpClient->request(
                'POST',
                $instanceUri->withPath('/api/v1/statuses'),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'status' => $vars['message'],
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
}
