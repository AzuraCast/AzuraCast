<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\UriInterface;

interface RouterInterface
{
    public function setRequest(?ServerRequest $request): void;

    public function withRequest(?ServerRequest $request): self;

    public function getBaseUrl(): UriInterface;

    public function buildBaseUrl(?bool $useRequest = null): UriInterface;

    /**
     * Simpler format for calling "named" routes with parameters.
     */
    public function named(
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): string;

    /**
     * Same as above, but returning a UriInterface.
     */
    public function namedAsUri(
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface;

    /**
     * Return a named route based on the current page and its route arguments.
     */
    public function fromHere(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): string;

    /**
     * Same as above, but returns a UriInterface.
     */
    public function fromHereAsUri(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface;

    /**
     * Same as $this->fromHere(), but merging the current GET query parameters into the request as well.
     */
    public function fromHereWithQuery(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): string;

    /**
     * Same as above, but returns a UriInterface.
     */
    public function fromHereWithQueryAsUri(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface;
}
