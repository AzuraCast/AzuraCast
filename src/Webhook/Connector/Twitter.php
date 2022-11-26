<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Enums\WebhookTriggers;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Service\GuzzleFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Monolog\Logger;

final class Twitter extends AbstractConnector
{
    public const NAME = 'twitter';

    public function __construct(
        Logger $logger,
        Client $httpClient,
        private readonly GuzzleFactory $guzzleFactory,
    ) {
        parent::__construct($logger, $httpClient);
    }

    protected function getRateLimitTime(StationWebhook $webhook): ?int
    {
        $config = $webhook->getConfig();
        $rateLimitSeconds = (int)($config['rate_limit'] ?? 0);

        return max(10, $rateLimitSeconds);
    }

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

        if (
            empty($config['consumer_key'])
            || empty($config['consumer_secret'])
            || empty($config['token'])
            || empty($config['token_secret'])
        ) {
            throw $this->incompleteConfigException(self::NAME);
        }

        // Set up Twitter OAuth
        $stack = clone $this->guzzleFactory->getHandlerStack();

        $middleware = new Oauth1(
            [
                'consumer_key' => trim($config['consumer_key']),
                'consumer_secret' => trim($config['consumer_secret']),
                'token' => trim($config['token']),
                'token_secret' => trim($config['token_secret']),
            ]
        );
        $stack->push($middleware);

        // Dispatch webhook
        $messages = [
            WebhookTriggers::SongChanged->value => $config['message'] ?? '',
            WebhookTriggers::LiveConnect->value => $config['message_live_connect'] ?? '',
            WebhookTriggers::LiveDisconnect->value => $config['message_live_disconnect'] ?? '',
            WebhookTriggers::StationOffline->value => $config['message_station_offline'] ?? '',
            WebhookTriggers::StationOnline->value => $config['message_station_online'] ?? '',
        ];

        foreach ($triggers as $trigger) {
            $message = $messages[$trigger] ?? '';
            if (empty($message)) {
                $this->logger->error(
                    'Cannot send Twitter message; message body for this trigger type is empty.'
                );
                return;
            }

            $vars = $this->replaceVariables(['message' => $message], $np);

            $this->logger->debug('Posting to Twitter...');

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
        }
    }
}
