<?php
namespace App\Webhook\Connector;

use App\Entity;
use App\Event\SendWebhooks;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use Monolog\Logger;

class Twitter extends AbstractConnector
{
    public function dispatch(SendWebhooks $event, array $config): void
    {
        if (empty($config['consumer_key'])
            || empty($config['consumer_secret'])
            || empty($config['token'])
            || empty($config['token_secret'])) {
            $this->logger->error('Webhook '.$this->_getName().' is missing necessary configuration. Skipping...');
            return;
        }

        // Set up Twitter OAuth

        /** @var HandlerStack $stack */
        $stack = clone $this->http_client->getConfig('handler');

        $middleware = new \GuzzleHttp\Subscriber\Oauth\Oauth1([
            'consumer_key'    => trim($config['consumer_key']),
            'consumer_secret' => trim($config['consumer_secret']),
            'token'           => trim($config['token']),
            'token_secret'    => trim($config['token_secret']),
        ]);
        $stack->push($middleware);

        $raw_vars = [
            'message' => $config['message'] ?? '',
        ];

        $vars = $this->_replaceVariables($raw_vars, $event->getNowPlaying());

        // Dispatch webhook
        $this->logger->debug('Posting to Twitter...');

        try {
            $response = $this->http_client->request('POST', 'https://api.twitter.com/1.1/statuses/update.json', [
                'auth' => 'oauth',
                'handler' => $stack,
                'form_params' => [
                    'status' => $vars['message'],
                ],
            ]);

            $this->logger->debug(
                sprintf('Twitter returned code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch(TransferException $e) {
            $this->logger->error(sprintf('Error from Twitter (%d): %s', $e->getCode(), $e->getMessage()));
        }
    }
}
