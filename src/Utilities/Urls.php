<?php

declare(strict_types=1);

namespace App\Utilities;

use GuzzleHttp\Psr7\Exception\MalformedUriException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class Urls
{
    public static function getUri(?string $url): ?UriInterface
    {
        if (null !== $url) {
            $url = trim($url);
            if (!empty($url)) {
                try {
                    return new Uri($url);
                } catch (MalformedUriException) {
                    /** @noinspection HttpUrlsUsage */
                    if (!str_starts_with($url, 'http')) {
                        return self::getUri('http://' . $url);
                    }
                }
            }
        }

        return null;
    }
}
