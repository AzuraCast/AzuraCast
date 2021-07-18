<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity;
use App\Environment;
use App\Traits\RequestAwareTrait;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;

class Router implements RouterInterface
{
    use RequestAwareTrait;

    protected ?UriInterface $baseUrl = null;

    public function __construct(
        protected Environment $environment,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected RouteParserInterface $routeParser
    ) {
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;
        $this->baseUrl = null;
    }

    public function getBaseUrl(bool $useRequest = true): UriInterface
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->buildBaseUrl();
        }

        return $this->baseUrl;
    }

    protected function buildBaseUrl(): UriInterface
    {
        $settings = $this->settingsRepo->readSettings();

        $settingsBaseUrl = $settings->getBaseUrl();
        if (!empty($settingsBaseUrl)) {
            if (!str_starts_with($settingsBaseUrl, 'http')) {
                /** @noinspection HttpUrlsUsage */
                $settingsBaseUrl = 'http://' . $settingsBaseUrl;
            }

            $baseUrl = new Uri($settingsBaseUrl);
        } else {
            $baseUrl = new Uri('');
        }

        $useHttps = $settings->getAlwaysUseSsl();

        if ($this->request instanceof ServerRequestInterface) {
            $currentUri = $this->request->getUri();

            if ('https' === $currentUri->getScheme()) {
                $useHttps = true;
            }

            $preferBrowserUrl = $settings->getPreferBrowserUrl();
            if ($preferBrowserUrl || $baseUrl->getHost() === '') {
                $ignoredHosts = ['web', 'nginx', 'localhost'];
                if (!in_array($currentUri->getHost(), $ignoredHosts, true)) {
                    $baseUrl = (new Uri())
                        ->withScheme($currentUri->getScheme())
                        ->withHost($currentUri->getHost())
                        ->withPort($currentUri->getPort());
                }
            }
        }

        if ($useHttps && $baseUrl->getScheme() !== '') {
            $baseUrl = $baseUrl->withScheme('https');
        }

        // Avoid double-trailing slashes in various URLs
        if ('/' === $baseUrl->getPath()) {
            $baseUrl = $baseUrl->withPath('');
        }

        // Filter the base URL so it doesn't say http://site:80 or https://site:443
        if (Uri::isDefaultPort($baseUrl)) {
            return $baseUrl->withPort(null);
        }

        return $baseUrl;
    }

    /**
     * @inheritDoc
     */
    public function fromHereWithQuery(
        ?string $route_name = null,
        array $route_params = [],
        array $query_params = [],
        bool $absolute = false
    ): UriInterface {
        if ($this->request instanceof ServerRequestInterface) {
            $query_params = array_merge($this->request->getQueryParams(), $query_params);
        }

        return $this->fromHere($route_name, $route_params, $query_params, $absolute);
    }

    /**
     * @inheritDoc
     */
    public function fromHere(
        ?string $route_name = null,
        array $route_params = [],
        array $query_params = [],
        bool $absolute = false
    ): UriInterface {
        if ($this->request instanceof ServerRequestInterface) {
            $route = RouteContext::fromRequest($this->request)->getRoute();
        } else {
            $route = null;
        }

        if (null === $route_name && $route instanceof RouteInterface) {
            $route_name = $route->getName();
        }

        if (null === $route_name) {
            throw new InvalidArgumentException(
                'Cannot specify a null route name if no existing route is configured.'
            );
        }

        if ($route instanceof RouteInterface) {
            $route_params = array_merge($route->getArguments(), $route_params);
        }

        return $this->named($route_name, $route_params, $query_params, $absolute);
    }

    /**
     * @inheritDoc
     */
    public function named(
        string $route_name,
        array $route_params = [],
        array $query_params = [],
        bool $absolute = false
    ): UriInterface {
        return self::resolveUri(
            $this->getBaseUrl(),
            $this->routeParser->relativeUrlFor($route_name, $route_params, $query_params),
            $absolute
        );
    }

    /**
     * Compose a URL, returning an absolute URL (including base URL) if the current settings or
     * this function's parameters indicate an absolute URL is necessary
     *
     * @param UriInterface $base
     * @param string|UriInterface $rel
     * @param bool $absolute
     */
    public static function resolveUri(
        UriInterface $base,
        UriInterface|string $rel,
        bool $absolute = false
    ): UriInterface {
        if (!$rel instanceof UriInterface) {
            $rel = new Uri($rel);
        }

        if (!$absolute) {
            return $rel;
        }

        // URI has an authority solely because of its port.
        if ($rel->getAuthority() !== '' && $rel->getHost() === '' && $rel->getPort()) {
            // Strip the authority from the URI, then reapply the port after the merge.
            $original_port = $rel->getPort();

            $new_uri = UriResolver::resolve($base, $rel->withScheme('')->withHost('')->withPort(null));
            return $new_uri->withPort($original_port);
        }

        return UriResolver::resolve($base, $rel);
    }
}
