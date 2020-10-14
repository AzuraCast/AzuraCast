<?php

namespace App\Middleware;

use App\Assets;
use App\Entity;
use App\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;

/**
 * Remove trailing slash from all URLs when routing.
 */
class EnforceSecurity implements MiddlewareInterface
{
    protected ResponseFactoryInterface $responseFactory;

    protected Entity\Repository\SettingsRepository $settings_repo;

    protected Assets $assets;

    public function __construct(
        App $app,
        Entity\Repository\SettingsRepository $settings_repo,
        Assets $assets
    ) {
        $this->responseFactory = $app->getResponseFactory();
        $this->settings_repo = $settings_repo;
        $this->assets = $assets;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $always_use_ssl = (bool)$this->settings_repo->getSetting('always_use_ssl', 0);
        $internal_api_url = mb_stripos($request->getUri()->getPath(), '/api/internal') === 0;

        // Assemble Content Security Policy (CSP)
        $csp = [];
        $add_hsts_header = false;

        if ('https' === $request->getUri()->getScheme()) {
            // Enforce secure cookies.
            ini_set('session.cookie_secure', '1');

            $csp[] = 'upgrade-insecure-requests';

            $add_hsts_header = true;
        } elseif ($always_use_ssl && !$internal_api_url) {
            return $this->responseFactory->createResponse(307)
                ->withHeader('Location', (string)$request->getUri()->withScheme('https'));
        }

        $response = $handler->handle($request);

        if ($add_hsts_header) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=3600');
        }

        // Set frame-deny header before next middleware, so it can be overwritten.
        $frameOptions = $response->getHeaderLine('X-Frame-Options');
        if ('*' === $frameOptions) {
            $response = $response->withoutHeader('X-Frame-Options');
        } else {
            $response = $response->withHeader('X-Frame-Options', 'DENY');
        }

        if (($response instanceof Response) && !$response->hasCacheLifetime()) {
            // CSP JavaScript policy
            // Note: unsafe-eval included for Vue template compiling
            $csp_script_src = $this->assets->getCspDomains();
            $csp_script_src[] = "'self'";
            $csp_script_src[] = "'unsafe-eval'";
            $csp_script_src[] = "'nonce-" . $this->assets->getCspNonce() . "'";

            $csp[] = 'script-src ' . implode(' ', $csp_script_src);

            $response = $response->withHeader('Content-Security-Policy', implode('; ', $csp));
        }

        return $response;
    }
}
