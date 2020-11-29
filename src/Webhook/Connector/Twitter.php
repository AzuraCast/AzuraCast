<?php

namespace App\Webhook\Connector;

use App\Entity\StationWebhook;
use App\Event\SendWebhooks;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Monolog\Logger;

class Twitter extends AbstractConnector
{
    public const NAME = 'twitter';

    protected EntityManagerInterface $em;

    public function __construct(Logger $logger, Client $http_client, EntityManagerInterface $em)
    {
        parent::__construct($logger, $http_client);

        $this->em = $em;
    }

    public function dispatch(SendWebhooks $event, StationWebhook $webhook): void
    {
        $config = $webhook->getConfig();

        if (
            empty($config['consumer_key'])
            || empty($config['consumer_secret'])
            || empty($config['token'])
            || empty($config['token_secret'])
        ) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return;
        }

        // Check rate limit

        $rate_limit_seconds = (int)($config['rate_limit'] ?? 0);
        if (0 !== $rate_limit_seconds) {
            $last_tweet = (int)$webhook->getMetadataKey('last_message_sent', 0);

            if ($last_tweet > (time() - $rate_limit_seconds)) {
                $this->logger->info(sprintf(
                    'A tweet was sent less than %d seconds ago. Skipping...',
                    $rate_limit_seconds
                ));
                return;
            }
        }

        // Set up Twitter OAuth

        /** @var HandlerStack $stack */
        $stack = clone $this->http_client->getConfig('handler');

        $middleware = new Oauth1([
            'consumer_key' => trim($config['consumer_key']),
            'consumer_secret' => trim($config['consumer_secret']),
            'token' => trim($config['token']),
            'token_secret' => trim($config['token_secret']),
        ]);
        $stack->push($middleware);

        $raw_vars = [
            'message' => $config['message'] ?? '',
        ];

        $vars = $this->replaceVariables($raw_vars, $event->getNowPlaying());

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
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from Twitter (%d): %s', $e->getCode(), $e->getMessage()));
        }

        $webhook->setMetadataKey('last_message_sent', time());
        $this->em->persist($webhook);
        $this->em->flush();
    }
}
