<?php
namespace App\Webhook\Connector;

use App\Entity;
use App\Event\SendWebhooks;
use App\Utilities;
use GuzzleHttp\Client;
use Monolog\Logger;

abstract class AbstractConnector implements ConnectorInterface
{
    /** @var Client */
    protected $http_client;

    /** @var Logger */
    protected $logger;

    public function __construct(Logger $logger, Client $http_client)
    {
        $this->logger = $logger;
        $this->http_client = $http_client;
    }

    public function shouldDispatch(SendWebhooks $event, array $triggers = null): bool
    {
        if (empty($triggers)) {
            return true;
        }

        foreach($triggers as $trigger) {
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
     * @return array
     */
    public function _replaceVariables(array $raw_vars, Entity\Api\NowPlaying $np): array
    {
        $values = Utilities::flattenArray($np, '.');
        $vars = [];

        foreach($raw_vars as $var_key => $var_value) {
            // Replaces {{ var.name }} with the flattened $values['var.name']
            $vars[$var_key] = preg_replace_callback("/\{\{(\s*)([a-zA-Z0-9\-_\.]+)(\s*)\}\}/", function($matches) use ($values) {
                $inner_value = strtolower(trim($matches[2]));
                return $values[$inner_value] ?? '';
            }, $var_value);
        }

        return $vars;
    }

    /**
     * Enhanced URL validation with support for unicode characters, courtesy of Symfony's URL validator
     *
     * @see https://github.com/symfony/Validator/blob/master/Constraints/UrlValidator.php
     * @author Bernhard Schussek <bschussek@gmail.com>
     */
    const URL_PATTERN = '~^
            (%s)://                                 # protocol
            (([\.\pL\pN-]+:)?([\.\pL\pN-]+)@)?      # basic auth
            (
                ([\pL\pN\pS\-\.])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                                 # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                    # an IP address
                    |                                                 # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # an IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
            (?:/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%%[0-9A-Fa-f]{2})* )*      # a path
            (?:\? (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?   # a query (optional)
            (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?   # a fragment (optional)
        $~ixu';

    /**
     * Determine if a passed URL is valid and return it if so, or return null otherwise.
     *
     * @param string $url_string
     * @return string|null
     */
    protected function _getValidUrl($url_string): ?string
    {
        $url = trim($url_string);
        $pattern = sprintf(self::URL_PATTERN, implode('|', ['http', 'https']));
        return (preg_match($pattern, $url)) ? $url : null;
    }

    /**
     * Get the displayable name of the connector class.
     *
     * @return string
     */
    protected function _getName(): string
    {
        $class_name = static::class;
        return array_pop(explode("\\", $class_name));
    }
}
