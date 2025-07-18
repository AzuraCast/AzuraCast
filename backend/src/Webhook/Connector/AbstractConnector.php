<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Container\LoggerAwareTrait;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\StationWebhook;
use App\Utilities\Arrays;
use App\Utilities\Types;
use App\Utilities\Urls;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractConnector implements ConnectorInterface
{
    use LoggerAwareTrait;

    public const string RATE_LIMIT_KEY = 'rate_limit';

    public function __construct(
        protected Client $httpClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function shouldDispatch(StationWebhook $webhook, array $triggers = []): bool
    {
        if (!$this->webhookShouldTrigger($webhook, $triggers)) {
            $this->logger->debug(
                sprintf(
                    'Webhook "%s" will not run for triggers: %s; skipping...',
                    $webhook->name,
                    implode(', ', $triggers)
                )
            );
            return false;
        }

        $rateLimitTime = $this->getRateLimitTime($webhook);
        if (null !== $rateLimitTime && !$webhook->checkRateLimit($rateLimitTime)) {
            $this->logger->notice(
                sprintf(
                    'Webhook "%s" has run less than %d seconds ago; skipping...',
                    $webhook->name,
                    $rateLimitTime
                )
            );
            return false;
        }

        return true;
    }

    /**
     * @param StationWebhook $webhook
     * @param array<string> $triggers
     *
     */
    protected function webhookShouldTrigger(StationWebhook $webhook, array $triggers = []): bool
    {
        if (empty($webhook->triggers ?? [])) {
            return true;
        }

        return array_any($triggers, fn($trigger) => $webhook->hasTrigger($trigger));
    }

    protected function getRateLimitTime(StationWebhook $webhook): ?int
    {
        $config = $webhook->config ?? [];
        return Types::intOrNull($config[self::RATE_LIMIT_KEY] ?? null);
    }

    /**
     * Replace variables in the format {{ blah }} with the flattened contents of the NowPlaying API array.
     *
     * @param array $rawVars
     * @param NowPlaying $np
     *
     * @return array
     */
    public function replaceVariables(array $rawVars, NowPlaying $np): array
    {
        $values = Arrays::flattenArray($np);

        // Replaces {{ var.name }} with the flattened $values['var.name']
        return array_map(function ($varValue) use ($values) {
            return preg_replace_callback(
                "/\{\{(\s*)([a-zA-Z\d\-_.]+)(\s*)}}/",
                static function (array $matches) use ($values): string {
                    $innerValue = strtolower(trim($matches[2]));
                    return Types::string($values[$innerValue] ?? '');
                },
                $varValue
            );
        }, $rawVars);
    }

    /**
     * Determine if a passed URL is valid and return it if so, or return null otherwise.
     */
    protected function getValidUrl(mixed $urlString = null): ?string
    {
        $urlString = Types::stringOrNull($urlString, true);

        $uri = Urls::tryParseUserUrl(
            $urlString,
            'Webhook'
        );

        if (null === $uri) {
            return null;
        }

        return (string)$uri;
    }

    protected function incompleteConfigException(StationWebhook $webhook): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf(
                'Webhook "%s" (type "%s") is missing necessary configuration. Skipping...',
                $webhook->name,
                $webhook->type->value
            ),
        );
    }

    protected function logHttpResponse(
        StationWebhook $webhook,
        ResponseInterface $response,
        mixed $requestBody = null
    ): void {
        $responseStatus = $response->getStatusCode();
        if ($responseStatus >= 400) {
            $this->logger->error(
                sprintf(
                    'Webhook "%s" returned unsuccessful response code %d.',
                    $webhook->name,
                    $responseStatus
                )
            );
        }

        $debugLogInfo = [];
        if ($requestBody) {
            $debugLogInfo['message_sent'] = $requestBody;
        }
        $debugLogInfo['response_body'] = $response->getBody()->getContents();

        $this->logger->debug(
            sprintf(
                'Webhook "%s" returned response code %d',
                $webhook->name,
                $response->getStatusCode()
            ),
            $debugLogInfo
        );
    }
}
