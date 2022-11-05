<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity;
use App\Traits\RequestAwareTrait;
use FastRoute\RouteParser\Std;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

final class Router implements RouterInterface
{
    use RequestAwareTrait;

    private ?UriInterface $baseUrl = null;

    private ?RouteInterface $currentRoute = null;

    private readonly array $routes;

    private readonly Std $routeParser;

    public function __construct(
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        RouteCollectorInterface $routeCollector
    ) {
        $routes = [];
        foreach ($routeCollector->getRoutes() as $route) {
            $routeName = $route->getName();
            if (null !== $routeName) {
                $routes[$routeName] = $route;
            }
        }
        $this->routes = $routes;

        $this->routeParser = new Std();
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;
        $this->baseUrl = null;

        $this->currentRoute = (null !== $request)
            ? RouteContext::fromRequest($request)->getRoute()
            : null;
    }

    public function getBaseUrl(): UriInterface
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->buildBaseUrl();
        }

        return $this->baseUrl;
    }

    public function buildBaseUrl(?bool $useRequest = null): UriInterface
    {
        $settings = $this->settingsRepo->readSettings();

        $useRequest ??= $settings->getPreferBrowserUrl();

        $baseUrl = $settings->getBaseUrlAsUri() ?? new Uri('');
        $useHttps = $settings->getAlwaysUseSsl();

        if ($this->request instanceof ServerRequestInterface) {
            $currentUri = $this->request->getUri();

            if ('https' === $currentUri->getScheme()) {
                $useHttps = true;
            }

            if ($useRequest || $baseUrl->getHost() === '') {
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
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface {
        if ($this->request instanceof ServerRequestInterface) {
            $queryParams = array_merge($this->request->getQueryParams(), $queryParams);
        }

        return $this->fromHere($routeName, $routeParams, $queryParams, $absolute);
    }

    /**
     * @inheritDoc
     */
    public function fromHere(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface {
        if (null !== $this->currentRoute) {
            if (null === $routeName) {
                $routeName = $this->currentRoute->getName();
            }

            $routeParams = array_merge($this->currentRoute->getArguments(), $routeParams);
        }

        if (null === $routeName) {
            throw new InvalidArgumentException(
                'Cannot specify a null route name if no existing route is configured.'
            );
        }

        return $this->named($routeName, $routeParams, $queryParams, $absolute);
    }

    /**
     * @inheritDoc
     */
    public function named(
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        bool $absolute = false
    ): UriInterface {
        $relativeUri = $this->getRelativeUri($routeName, $routeParams, $queryParams);

        return ($absolute)
            ? self::resolveUri($this->getBaseUrl(), $relativeUri, true)
            : $relativeUri;
    }

    private function getRelativeUri(string $routeName, array $data = [], array $queryParams = []): UriInterface
    {
        if (!isset($this->routes[$routeName])) {
            throw new \InvalidArgumentException('Named route does not exist for name: ' . $routeName);
        }

        $pattern = $this->routes[$routeName]->getPattern();

        $segments = [];
        $segmentName = '';

        /*
         * $routes is an associative array of expressions representing a route as multiple segments
         * There is an expression for each optional parameter plus one without the optional parameters
         * The most specific is last, hence why we reverse the array before iterating over it
         */
        foreach (array_reverse($this->routeParser->parse($pattern)) as $expression) {
            foreach ($expression as $segment) {
                /*
                 * Each $segment is either a string or an array of strings
                 * containing optional parameters of an expression
                 */
                if (is_string($segment)) {
                    $segments[] = $segment;
                    continue;
                }

                /** @var string[] $segment */
                /*
                 * If we don't have a data element for this segment in the provided $data
                 * we cancel testing to move onto the next expression with a less specific item
                 */
                if (!array_key_exists($segment[0], $data)) {
                    $segments = [];
                    $segmentName = $segment[0];
                    break;
                }

                $segments[] = $data[$segment[0]];
            }

            /*
             * If we get to this logic block we have found all the parameters
             * for the provided $data which means we don't need to continue testing
             * less specific expressions
             */
            if (!empty($segments)) {
                break;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }

        $url = new Uri(implode('', $segments));
        return ($queryParams)
            ? $url->withQuery(http_build_query($queryParams))
            : $url;
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
            $originalPort = $rel->getPort();

            $newUri = UriResolver::resolve($base, $rel->withScheme('')->withHost('')->withPort(null));
            return $newUri->withPort($originalPort);
        }

        return UriResolver::resolve($base, $rel);
    }
}
