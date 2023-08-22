<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;

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

        $botToken = trim($config['bot_token'] ?? '');
        $chatId = trim($config['chat_id'] ?? '');

        if (empty($botToken) || empty($chatId)) {
            throw $this->incompleteConfigException($webhook);
        }

        $messages = $this->replaceVariables(
            [
                'text' => $config['text'],
            ],
            $np
        );

        $apiUrl = (!empty($config['api'])) ? rtrim($config['api'], '/') : 'https://api.telegram.org';
        $webhookUrl = $apiUrl . '/bot' . $botToken . '/sendMessage';

        $requestParams = [
            'chat_id' => $chatId,
            'text' => $messages['text'],
            'parse_mode' => $config['parse_mode'] ?? 'Markdown', // Markdown or HTML
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
