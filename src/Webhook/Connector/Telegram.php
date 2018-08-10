<?php
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
    /**
     * @param Entity\Station $station
     * @param Entity\Api\NowPlaying $np
     * @param array $config
     */
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np, array $config): void
    {
        $bot_token = $config['bot_token'] ?? '';
        $chat_id = $config['chat_id'] ?? '';

        if (empty($bot_token) || empty($chat_id)) {
            $this->logger->error('Webhook '.get_called_class().' is missing necessary configuration. Skipping...');
            return;
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.telegram.org/', // bot<bot_token>/sendMessage?chat_id=-1001433&text=LIVE',
            'http_errors' => false,
            'timeout' => 2.0,
        ]);

        $messages = $this->_replaceVariables([
            'text' => $config['text'],
        ], $np);

        try {
            $webhook_url = '/bot'.$bot_token.'/sendMessage';

            $response = $client->request('POST', $webhook_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'chat_id' => $chat_id,
                    'text' => $messages['text'],
                    'parse_mode' => $config['parse_mode'] ?? 'Markdown' // Markdown or HTML
                ],
            ]);

            $this->logger->debug(
                sprintf('Webhook %s code %d', get_called_class(), $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch(TransferException $e) {
            $this->logger->error(sprintf('Error from webhook %s (%d): %s', get_called_class(), $e->getCode(), $e->getMessage()));
        }
    }
}
