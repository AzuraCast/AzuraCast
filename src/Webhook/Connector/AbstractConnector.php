<?php

namespace App\Webhook\Connector;

use App\Entity;
use App\Entity\StationWebhook;
use App\Event\SendWebhooks;
use App\Utilities;
use GuzzleHttp\Client;
use Monolog\Logger;
use Symfony\Component\Validator\Constraints\UrlValidator;

abstract class AbstractConnector implements ConnectorInterface
{
    protected Client $http_client;

    protected Logger $logger;

    public function __construct(Logger $logger, Client $http_client)
    {
        $this->logger = $logger;
        $this->http_client = $http_client;
    }

    public function shouldDispatch(SendWebhooks $event, StationWebhook $webhook): bool
    {
        $triggers = $webhook->getTriggers();

        if (empty($triggers)) {
            return true;
        }

        foreach ($triggers as $trigger) {
            if ($event->hasTrigger($trigger)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace variables in the format {{ blah }} with the flattened contents of the NowPlaying API array.
     *
     * @param array $raw_vars
     * @param Entity\Api\NowPlaying $np
     *
     * @return mixed[]
     */
    public function replaceVariables(array $raw_vars, Entity\Api\NowPlaying $np): array
    {
        $values = Utilities::flattenArray($np, '.');
        $vars = [];

        foreach ($raw_vars as $var_key => $var_value) {
            // Replaces {{ var.name }} with the flattened $values['var.name']
            $vars[$var_key] = preg_replace_callback(
                "/\{\{(\s*)([a-zA-Z0-9\-_\.]+)(\s*)\}\}/",
                function ($matches) use ($values) {
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
        $url = trim($url_string);
        $pattern = sprintf(UrlValidator::PATTERN, implode('|', ['http', 'https']));
        return (preg_match($pattern, $url)) ? $url : null;
    }
}
