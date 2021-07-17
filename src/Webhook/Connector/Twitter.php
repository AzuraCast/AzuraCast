<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;
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

    public function __construct(Logger $logger, Client $httpClient, EntityManagerInterface $em)
    {
        parent::__construct($logger, $httpClient);

        $this->em = $em;
    }

    protected function getRateLimitTime(Entity\StationWebhook $webhook): ?int
    {
        $config = $webhook->getConfig();
        $rateLimitSeconds = (int)($config['rate_limit'] ?? 0);

        return max(10, $rateLimitSeconds);
    }

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

        if (
            empty($config['consumer_key'])
            || empty($config['consumer_secret'])
            || empty($config['token'])
            || empty($config['token_secret'])
        ) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return false;
        }

        // Set up Twitter OAuth
        /** @var HandlerStack $stack */
        $stack = clone $this->httpClient->getConfig('handler');

        $middleware = new Oauth1(
            [
                'consumer_key' => trim($config['consumer_key']),
                'consumer_secret' => trim($config['consumer_secret']),
                'token' => trim($config['token']),
                'token_secret' => trim($config['token_secret']),
            ]
        );
        $stack->push($middleware);

        $raw_vars = [
            'message' => $config['message'] ?? '',
        ];

        $vars = $this->replaceVariables($raw_vars, $np);

        // Dispatch webhook
        $this->logger->debug('Posting to Twitter...');

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://api.twitter.com/1.1/statuses/update.json',
                [
                    'auth' => 'oauth',
                    'handler' => $stack,
                    'form_params' => [
                        'status' => $vars['message'],
                    ],
                ]
            );

            $this->logger->debug(
                sprintf('Twitter returned code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from Twitter (%d): %s', $e->getCode(), $e->getMessage()));
            return false;
        }

        return true;
    }
}
