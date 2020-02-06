<?php
namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

interface RouterInterface
{
    /**
     * @param ServerRequestInterface $current_request
     */
    public function setCurrentRequest(ServerRequestInterface $current_request): void;

    /**
     * @return ServerRequestInterface
     */
    public function getCurrentRequest(): ServerRequestInterface;

    /**
     * Simpler format for calling "named" routes with parameters.
     *
     * @param string $route_name
     * @param array $route_params
     * @param array $query_params
     * @param boolean $absolute Whether to include the full URL.
     *
     * @return UriInterface
     */
    public function named($route_name, $route_params = [], array $query_params = [], $absolute = false): UriInterface;

    /**
     * Dynamically calculate the base URL the first time it's called, if it is at all in the request.
     *
     * @param bool $use_request Use the current request for the base URI, if available.
     *
     * @return UriInterface
     */
    public function getBaseUrl(bool $use_request = true): UriInterface;

    /**
     * Return a named route based on the current page and its route arguments.
     *
     * @param null $route_name
     * @param array $route_params
     * @param array $query_params
     * @param bool $absolute
     *
     * @return string
     */
    public function fromHere(
        $route_name = null,
        array $route_params = [],
        array $query_params = [],
        $absolute = false
    ): string;

    /**
     * Same as $this->fromHere(), but merging the current GET query parameters into the request as well.
     *
     * @param null $route_name
     * @param array $route_params
     * @param array $query_params
     * @param bool $absolute
     *
     * @return string
     */
    public function fromHereWithQuery(
        $route_name = null,
        array $route_params = [],
        array $query_params = [],
        $absolute = false
    ): string;
}