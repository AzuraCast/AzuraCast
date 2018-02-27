<?php
namespace AzuraCast\Webhook\Connector;

use Entity;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

class Twitter extends AbstractConnector
{
    /**
     * @param Entity\Station $station
     * @param Entity\Api\NowPlaying $np
     * @param array $config
     */
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np, array $config): void
    {
        if (empty($config['consumer_key'])
            || empty($config['consumer_secret'])
            || empty($config['token'])
            || empty($config['token_secret'])) {
            $this->logger->error('Webhook '.get_called_class().' is missing necessary configuration. Skipping...');
            return;
        }

        // Set up Twitter OAuth
        $stack = \GuzzleHttp\HandlerStack::create();
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

        $vars = $this->_replaceVariables($raw_vars, $np);

        // Dispatch webhook
        $this->logger->debug('Posting to Twitter...');

        $client = new \GuzzleHttp\Client([
            'http_errors' => false,
            'timeout' => 2.0,
            'auth' => 'oauth',
            'handler' => $stack,
        ]);

        try {
            $response = $client->request('POST', 'https://api.twitter.com/1.1/statuses/update.json', [
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