<?php

declare(strict_types=1);

namespace App\Utilities;

use GuzzleHttp\Psr7\Uri;
use LogicException;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Throwable;

final class Urls
{
    public static function getUri(
        ?string $url,
        bool $mustBeAbsolute = true
    ): UriInterface {
        if (null === $url) {
            throw new RuntimeException('URL field is empty.');
        }

        $url = trim($url);
        if (empty($url)) {
            throw new RuntimeException('URL field is empty.');
        }

        $uri = new Uri($url);

        if ($mustBeAbsolute) {
            if ('' === $uri->getHost() && '' === $uri->getScheme()) {
                return self::getUri('https://' . $url);
            }

            if ('' === $uri->getScheme()) {
                $uri = $uri->withScheme('http');
            }
        }

        if (!in_array($uri->getScheme(), ['', 'http', 'https'], true)) {
            throw new RuntimeException('Invalid URL scheme.');
        }

        if ('/' === $uri->getPath()) {
            $uri = $uri->withPath('');
        }

        if (Uri::isDefaultPort($uri)) {
            $uri = $uri->withPort(null);
        }

        return $uri;
    }

    public static function parseUserUrl(
        ?string $url,
        string $context,
        bool $mustBeAbsolute = true
    ): UriInterface {
        try {
            return self::getUri($url, $mustBeAbsolute);
        } catch (Throwable $e) {
            throw new LogicException(
                message: sprintf('Could not parse %s URL "%s": %s', $context, $url, $e->getMessage()),
                previous: $e
            );
        }
    }

    public static function tryParseUserUrl(
        ?string $url,
        string $context,
        bool $mustBeAbsolute = true
    ): ?UriInterface {
        if (empty($url)) {
            return null;
        }

        try {
            return self::getUri($url, $mustBeAbsolute);
        } catch (Throwable $e) {
            Logger::getInstance()->notice(
                sprintf('Could not parse %s URL "%s"', $context, $url),
                [
                    'exception' => $e,
                ]
            );

            return null;
        }
    }
}
