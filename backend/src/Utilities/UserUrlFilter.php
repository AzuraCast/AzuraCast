<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Container\SettingsAwareTrait;
use LogicException;
use PhpIP\IP;
use Throwable;

final class UserUrlFilter
{
    use SettingsAwareTrait;

    /**
     * Attempt to parse a user-supplied URL that could possibly dispatch an HTTP connection.
     *
     * If the global setting to do so is enabled, this will attempt to retrieve the IP behind the
     * URL supplied and determine if it is an internal or reserved IP address. If so, it will
     * reject the URL and throw an exception.
     *
     * @param mixed|null $url
     * @param string|null $context
     * @param bool $mustBeAbsolute
     * @return string
     */
    public function filterSensitiveUserUrl(
        mixed $url = null,
        ?string $context = null,
        bool $mustBeAbsolute = true
    ): string {
        $url = Types::stringOrNull($url, true);

        $uri = Urls::parseUserUrl(
            url: $url,
            context: $context ?? 'User-Supplied URL',
            mustBeAbsolute: $mustBeAbsolute
        );

        $settings = $this->readSettings();

        if (!$settings->filter_local_user_urls) {
            return (string)$uri;
        }

        // Look for internal IPs and filter them out.
        try {
            $ip = IP::create($uri->getHost());
        } catch (Throwable) {
            $hostIp = gethostbyname(rtrim($uri->getHost(), '.') . '.');

            try {
                $ip = IP::create($hostIp);
            } catch (Throwable $e) {
                throw new LogicException(
                    message: sprintf('Could not fetch %s IP for URL "%s": %s', $context, $hostIp, $e->getMessage()),
                    previous: $e
                );
            }
        }

        if ($ip->isLinkLocal()) {
            throw new LogicException(
                message: sprintf(
                    'Resolved IP for %s URL "%s" (%s) is link-local. Internal URLs are disabled for this installation.',
                    $context,
                    $uri,
                    $ip
                )
            );
        }

        if ($ip->isLoopback()) {
            throw new LogicException(
                message: sprintf(
                    'Resolved IP for %s URL "%s" (%s) is loopback. Internal URLs are disabled for this installation.',
                    $context,
                    $uri,
                    $ip
                )
            );
        }

        if ($ip->isReserved()) {
            throw new LogicException(
                message: sprintf(
                    'Resolved IP for %s URL "%s" (%s) is reserved. Internal URLs are disabled for this installation.',
                    $context,
                    $uri,
                    $ip
                )
            );
        }

        if ($ip->isPrivate()) {
            throw new LogicException(
                message: sprintf(
                    'Resolved IP for %s URL "%s" (%s) is private. Internal URLs are disabled for this installation.',
                    $context,
                    $uri,
                    $ip
                )
            );
        }

        return (string)$uri;
    }
}
