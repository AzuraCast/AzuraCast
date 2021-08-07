<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;
use GuzzleHttp\Exception\TransferException;

/**
 * Telegram web hook connector.
 *
 * @package App\Webhook\Connector
 */
class Telegram extends AbstractConnector
{
    public const NAME = 'telegram';

    /**
     * @inheritDoc
     */
    public function dispatch(
        Entity\Station $station,
        Entity\StationWebhook $webhook,
        Entity\Api\NowPlaying $np,
        array $triggers
    ): bool {
        $config = $webhook->getConfig();

        $bot_token = trim($config['bot_token'] ?? '');
        $chat_id = trim($config['chat_id'] ?? '');

        if (empty($bot_token) || empty($chat_id)) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return false;
        }

        $messages = $this->replaceVariables(
            [
                'text' => $config['text'],
            ],
            $np
        );

        try {
            $api_url = (!empty($config['api'])) ? rtrim($config['api'], '/') : 'https://api.telegram.org';
            $webhook_url = $api_url . '/bot' . $bot_token . '/sendMessage';

            $request_params = [
                'chat_id' => $chat_id,
                'text' => $messages['text'],
                'parse_mode' => $config['parse_mode'] ?? 'Markdown', // Markdown or HTML
            ];

            $response = $this->httpClient->request(
                'POST',
                $webhook_url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $request_params,
                ]
            );

            $this->logger->debug(
                sprintf('Webhook %s returned code %d', self::NAME, $response->getStatusCode()),
                [
                    'request_url' => $webhook_url,
                    'request_params' => $request_params,
                    'response_body' => $response->getBody()->getContents(),
                ]
            );
        } catch (TransferException $e) {
            $this->logger->error(
                sprintf(
                    'Error from webhook %s (%d): %s',
                    self::NAME,
                    $e->getCode(),
                    $e->getMessage()
                )
            );

            return false;
        }

        return true;
    }
}
