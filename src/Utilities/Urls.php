<?php

declare(strict_types=1);

namespace App\Utilities;

use Exception;
use GuzzleHttp\Psr7\Exception\MalformedUriException;
use GuzzleHttp\Psr7\Uri;
use LogicException;
use Psr\Http\Message\UriInterface;

final class Urls
{
    public static function getUri(?string $url): ?UriInterface
    {
        if (null !== $url) {
            $url = trim($url);
            if (!empty($url)) {
                try {
                    return new Uri($url);
                } catch (MalformedUriException) {
                    if (!str_starts_with($url, 'http')) {
                        /** @noinspection HttpUrlsUsage */
                        return self::getUri('http://' . $url);
                    }
                }
            }
        }

        return null;
    }

    public static function parseUserUrl(
        ?string $url,
        string $context
    ): UriInterface {
        try {
            if (empty($url)) {
                throw new LogicException('No URL specified.');
            }

            $url = trim($url);
            try {
                return new Uri($url);
            } catch (MalformedUriException $ex) {
                if (!str_starts_with($url, 'http')) {
                    /** @noinspection HttpUrlsUsage */
                    return new Uri('http://' . $url);
                }
                throw $ex;
            }
        } catch (Exception $e) {
            throw new LogicException(
                message: sprintf('Could not parse %s URL "%s": %s', $context, $url, $e->getMessage()),
                previous: $e
            );
        }
    }

    public static function tryParseUserUrl(
        ?string $url,
        string $context
    ): ?UriInterface {
        if (empty($url)) {
            return null;
        }

        try {
            return self::parseUserUrl($url, $context);
        } catch (Exception $e) {
            Logger::getInstance()->error(
                sprintf('Could not parse %s URL "%s": %s', $context, $url, $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );
            return null;
        }
    }
}
