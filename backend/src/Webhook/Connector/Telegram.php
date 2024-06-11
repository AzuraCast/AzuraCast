<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Utilities\Types;

/**
 * Telegram web hook connector.
 *
 * @package App\Webhook\Connector
 */
final class Telegram extends AbstractConnector
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
        $config = $webhook->getConfig();

        $botToken = Types::stringOrNull($config['bot_token'], true);
        $chatId = Types::stringOrNull($config['chat_id'], true);

        if (null === $botToken || null === $chatId) {
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
            : 'https://api.telegram.org';

        $webhookUrl = $apiUrl . '/bot' . $botToken . '/sendMessage';

        $requestParams = [
            'chat_id' => $chatId,
            'text' => $messages['text'],
            'parse_mode' => Types::stringOrNull($config['parse_mode'], true) ?? 'Markdown', // Markdown or HTML
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
    }
}
