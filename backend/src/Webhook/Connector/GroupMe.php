<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Utilities\Types;

/**
 * GroupMe web hook connector.
 *
 * @package App\Webhook\Connector
 */
final class GroupMe extends AbstractConnector
{
    /**
     * @inheritDoc
     */
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->config ?? [];

        $botId = Types::stringOrNull($config['bot_id'], true);

        if (null === $botId) {
            throw $this->incompleteConfigException($webhook);
        }

        $messages = $this->replaceVariables(
            [
                'text' => $config['text'],
            ],
            $np
        );

        $apiUrl = Types::stringOrNull($config['api'], true);
        $apiUrl = (null !== $apiUrl)
            ? rtrim($apiUrl, '/')
            : 'https://api.groupme.com/v3';

        $webhookUrl = $apiUrl . '/bots/post';

        $text = $messages['text'];
        $chunks = str_split($text, 1000);

        foreach ($chunks as $chunk) {
            $requestParams = [
                'bot_id' => $botId,
                'text' => $chunk,
            ];

            $response = $this->httpClient->request(
                'POST',
                $webhookUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $requestParams,
                ]
            );

            $this->logHttpResponse(
                $webhook,
                $response,
                $requestParams
            );
            usleep(100000); // Delay to prevent sending out of order
        }
    }
}
