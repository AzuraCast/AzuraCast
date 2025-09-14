<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Http\Router;
use JsonSerializable;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;
use Stringable;

#[OA\Schema(
    schema: 'Api_ResolvableUrl',
    type: 'string'
)]
final class ResolvableUrl implements JsonSerializable, Stringable
{
    public function __construct(
        private string|UriInterface $url
    ) {
    }

    public function resolveUrl(?UriInterface $base = null): string
    {
        if ($this->url instanceof UriInterface) {
            if (null === $base) {
                $router = Router::getInstance();
                $base = $router->getBaseUrl();
            }

            $this->url = (string)Router::resolveUri($base, $this->url, true);
        }

        return $this->url;
    }

    public function __toString(): string
    {
        return $this->resolveUrl();
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
