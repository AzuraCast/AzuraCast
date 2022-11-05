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

    public function buildBaseUrl(?bool $useRequest = null): UriInterface;

    /**
     * Simpler format for calling "named" routes with parameters.
     *
     * @param string $routeName
     * @param array $routeParams
     * @param array $queryParams
     * @param boolean $absolute Whether to include the full URL.
     */
    public function named(
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface;

    /**
     * Return a named route based on the current page and its route arguments.
     *
     * @param string|null $routeName
     * @param array $routeParams
     * @param array $queryParams
     * @param bool $absolute
     */
    public function fromHere(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface;

    /**
     * Same as $this->fromHere(), but merging the current GET query parameters into the request as well.
     *
     * @param string|null $routeName
     * @param array $routeParams
     * @param array $queryParams
     * @param bool $absolute
     */
    public function fromHereWithQuery(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface;
}
