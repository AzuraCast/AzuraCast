<?php
namespace App\Webhook\Connector;

use App\Event\SendWebhooks;
use GuzzleHttp\Exception\TransferException;

/**
 * Telegram web hook connector.
 *
 * @package App\Webhook\Connector
 */
class Telegram extends AbstractConnector
{
    public function dispatch(SendWebhooks $event, array $config): void
    {
        $bot_token = $config['bot_token'] ?? '';
        $chat_id = $config['chat_id'] ?? '';

        if (empty($bot_token) || empty($chat_id)) {
            $this->logger->error('Webhook '.$this->_getName().' is missing necessary configuration. Skipping...');
            return;
        }

        $messages = $this->_replaceVariables([
            'text' => $config['text'],
        ], $event->getNowPlaying());

        try {
            $api_url = (!empty($config['api'])) ? rtrim($config['api'], '/') : 'https://api.telegram.org';
            $webhook_url = $api_url.'/bot'.$bot_token.'/sendMessage';

            $request_params = [
                'chat_id' => $chat_id,
                'text' => $messages['text'],
                'parse_mode' => $config['parse_mode'] ?? 'Markdown' // Markdown or HTML
            ];

            $response = $this->http_client->request('POST', $webhook_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $request_params,
            ]);

            $this->logger->debug(
                sprintf('Webhook %s returned code %d', $this->_getName(), $response->getStatusCode()),
                [
                    'request_url' => $webhook_url,
                    'request_params' => $request_params,
                    'response_body' => $response->getBody()->getContents()
                ]
            );
        } catch(TransferException $e) {
            $this->logger->error(sprintf('Error from webhook %s (%d): %s', $this->_getName(), $e->getCode(), $e->getMessage()));
        }
    }
}
