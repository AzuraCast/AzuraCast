<?php

declare(strict_types=1);

namespace App\Middleware\Module;

use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Handle API calls and wrap exceptions in JSON formatting.
 */
class Api
{
    public function __construct(
        protected Entity\Repository\ApiKeyRepository $api_repo,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Environment $environment
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->environment->isDevelopment()) {
            $cloner = new VarCloner();
            $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

            $dumper = new CliDumper('php://output');
            VarDumper::setHandler(
                function ($var) use ($cloner, $dumper): void {
                    $dumper->dump($cloner->cloneVar($var));
                }
            );
        }

        // Attempt API key auth if a key exists.
        $api_key = $this->getApiKey($request);
        $api_user = (!empty($api_key)) ? $this->api_repo->authenticate($api_key) : null;

        // Override the request's "user" variable if API authentication is supplied and valid.
        if ($api_user instanceof Entity\User) {
            $request = $request->withAttribute(ServerRequest::ATTR_USER, $api_user);
            $request->getAcl()->setRequest($request);

            Entity\AuditLog::setCurrentUser($api_user);
        }

        // Set default cache control for API pages.
        $settings = $this->settingsRepo->readSettings();

        $prefer_browser_url = $settings->getPreferBrowserUrl();

        $response = $handler->handle($request);

        // Check for a user-set CORS header override.
        $acao_header = trim($settings->getApiAccessControl());
        if (!empty($acao_header)) {
            if ('*' === $acao_header) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            } else {
                // Return the proper ACAO header matching the origin (if one exists).
                $origin = $request->getHeaderLine('Origin');

                if (!empty($origin)) {
                    $rawOrigins = array_map('trim', explode(',', $acao_header));

                    $baseUrl = $settings->getBaseUrl();
                    if (null !== $baseUrl) {
                        $rawOrigins[] = $baseUrl;
                    }

                    $origins = [];
                    foreach ($rawOrigins as $rawOrigin) {
                        $uri = new Uri($rawOrigin);
                        if (empty($uri->getScheme())) {
                            $origins[] = (string)($uri->withScheme('http'));
                            $origins[] = (string)($uri->withScheme('https'));
                        } else {
                            $origins[] = (string)$uri;
                        }
                    }

                    $origins = array_unique($origins);
                    if (in_array($origin, $origins, true)) {
                        $response = $response
                            ->withHeader('Access-Control-Allow-Origin', $origin)
                            ->withHeader('Vary', 'Origin');
                    }
                }
            }
        } elseif ($api_user instanceof Entity\User || in_array($request->getMethod(), ['GET', 'OPTIONS'])) {
            // Default behavior:
            // Only set global CORS for GET requests and API-authenticated requests;
            // Session-authenticated, non-GET requests should only be made in a same-host situation.
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        if ($response instanceof Response && !$response->hasCacheLifetime()) {
            if ($prefer_browser_url || $request->getAttribute(ServerRequest::ATTR_USER) instanceof Entity\User) {
                $response = $response->withNoCache();
            } else {
                $response = $response->withCacheLifetime(15);
            }
        }

        return $response;
    }

    /**
     * @param ServerRequest $request
     */
    protected function getApiKey(ServerRequest $request): ?string
    {
        // Check authorization header
        $auth_headers = $request->getHeader('Authorization');
        $auth_header = $auth_headers[0] ?? '';

        if (preg_match("/Bearer\s+(.*)$/i", $auth_header, $matches)) {
            return $matches[1];
        }

        // Check API key header
        $api_key_headers = $request->getHeader('X-API-Key');
        if (!empty($api_key_headers[0])) {
            return $api_key_headers[0];
        }

        // Check cookies
        $cookieParams = $request->getCookieParams();
        if (!empty($cookieParams['token'])) {
            return $cookieParams['token'];
        }

        // Check URL parameters as last resort
        $queryApiKey = $request->getQueryParam('api-key');
        if (!empty($queryApiKey)) {
            return $queryApiKey;
        }

        return null;
    }
}
