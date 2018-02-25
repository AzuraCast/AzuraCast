<?php
namespace AzuraCast\Webhook\Connector;

use Entity;
use GuzzleHttp\Exception\TransferException;

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
            \App\Debug::log('Webhook is missing necessary configuration. Skipping...');
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
        \App\Debug::log('Posting to Twitter...');

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

            \App\Debug::log(sprintf('Twitter returned code %d', $response->getStatusCode()));
            \App\Debug::print_r($response->getBody()->getContents());
        } catch(TransferException $e) {
            \App\Debug::log(sprintf('Error from Twitter (%d): %s', $e->getCode(), $e->getMessage()));
        }
    }
}