<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

interface RouterInterface
{
    public function setRequest(?ServerRequestInterface $request): void;

    public function withRequest(?ServerRequestInterface $request): self;

    public function getBaseUrl(): UriInterface;

    /**
     * Simpler format for calling "named" routes with parameters.
     *
     * @param string $route_name
     * @param array $route_params
     * @param array $query_params
     * @param boolean $absolute Whether to include the full URL.
     */
    public function named(
        string $route_name,
        array $route_params = [],
        array $query_params = [],
        bool $absolute = false
    ): UriInterface;

    /**
     * Return a named route based on the current page and its route arguments.
     *
     * @param string|null $route_name
     * @param array $route_params
     * @param array $query_params
     * @param bool $absolute
     */
    public function fromHere(
        ?string $route_name = null,
        array $route_params = [],
        array $query_params = [],
        bool $absolute = false
    ): UriInterface;

    /**
     * Same as $this->fromHere(), but merging the current GET query parameters into the request as well.
     *
     * @param string|null $route_name
     * @param array $route_params
     * @param array $query_params
     * @param bool $absolute
     */
    public function fromHereWithQuery(
        ?string $route_name = null,
        array $route_params = [],
        array $query_params = [],
        bool $absolute = false
    ): UriInterface;
}
