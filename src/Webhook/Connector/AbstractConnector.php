<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;
use App\Utilities;
use GuzzleHttp\Client;
use Monolog\Logger;
use Symfony\Component\Validator\Constraints\UrlValidator;

abstract class AbstractConnector implements ConnectorInterface
{
    public function __construct(
        protected Logger $logger,
        protected Client $httpClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function shouldDispatch(Entity\StationWebhook $webhook, array $triggers = []): bool
    {
        if (!$this->webhookShouldTrigger($webhook, $triggers)) {
            $this->logger->debug(
                sprintf(
                    'Webhook "%s" will not run for triggers: %s; skipping...',
                    $webhook->getName(),
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
                    $webhook->getName(),
                    $rateLimitTime
                )
            );
            return false;
        }

        return true;
    }

    /**
     * @param Entity\StationWebhook $webhook
     * @param array<string> $triggers
     *
     */
    protected function webhookShouldTrigger(Entity\StationWebhook $webhook, array $triggers = []): bool
    {
        $webhookTriggers = $webhook->getTriggers();
        if (empty($webhookTriggers)) {
            return true;
        }

        foreach ($webhookTriggers as $trigger) {
            if (in_array($trigger, $triggers, true)) {
                return true;
            }
        }

        return false;
    }

    protected function getRateLimitTime(Entity\StationWebhook $webhook): ?int
    {
        return 10;
    }

    /**
     * Replace variables in the format {{ blah }} with the flattened contents of the NowPlaying API array.
     *
     * @param array<mixed> $raw_vars
     * @param Entity\Api\NowPlaying $np
     *
     * @return array<mixed>
     */
    public function replaceVariables(array $raw_vars, Entity\Api\NowPlaying $np): array
    {
        $values = Utilities\Arrays::flattenArray($np);
        $vars = [];

        foreach ($raw_vars as $var_key => $var_value) {
            // Replaces {{ var.name }} with the flattened $values['var.name']
            $vars[$var_key] = preg_replace_callback(
                "/\{\{(\s*)([a-zA-Z0-9\-_\.]+)(\s*)\}\}/",
                static function ($matches) use ($values) {
                    $inner_value = strtolower(trim($matches[2]));
                    return $values[$inner_value] ?? '';
                },
                $var_value
            );
        }

        return $vars;
    }

    /**
     * Determine if a passed URL is valid and return it if so, or return null otherwise.
     *
     * @param string|null $url_string
     */
    protected function getValidUrl(?string $url_string = null): ?string
    {
        $url = trim($url_string ?? '');
        $pattern = sprintf(UrlValidator::PATTERN, 'http|https');
        return (preg_match($pattern, $url)) ? $url : null;
    }
}
