<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Container\SettingsAwareTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;

/**
 * Remove trailing slash from all URLs when routing.
 */
final class EnforceSecurity implements MiddlewareInterface
{
    use SettingsAwareTrait;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        App $app
    ) {
        $this->responseFactory = $app->getResponseFactory();
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $alwaysUseSsl = $this->readSettings()->getAlwaysUseSsl();

        $internalApiUrl = mb_stripos($request->getUri()->getPath(), '/api/internal') === 0;

        $addHstsHeader = false;
        if ('https' === $request->getUri()->getScheme()) {
            $addHstsHeader = true;
        } elseif ($alwaysUseSsl && !$internalApiUrl) {
            return $this->responseFactory->createResponse(307)
                ->withHeader('Location', (string)$request->getUri()->withScheme('https'));
        }

        $response = $handler->handle($request);

        if ($addHstsHeader) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=3600');
        }

        // Opt out of FLoC
        $permissionsPolicies = [
            'autoplay=*', // Explicitly allow autoplay
            'fullscreen=*', // Explicitly allow fullscreen
            'interest-cohort=()', // Disable FLoC tracking
        ];

        $response = $response->withHeader('Permissions-Policy', implode(', ', $permissionsPolicies));

        // Deny crawling on any pages that don't explicitly allow it.
        $robotsHeader = $response->getHeaderLine('X-Robots-Tag');
        if ('' === $robotsHeader) {
            $response = $response->withHeader('X-Robots-Tag', 'noindex, nofollow');
        }

        // Set frame-deny header before next middleware, so it can be overwritten.
        $frameOptions = $response->getHeaderLine('X-Frame-Options');
        if ('*' === $frameOptions) {
            $response = $response->withoutHeader('X-Frame-Options');
        } else {
            $response = $response->withHeader('X-Frame-Options', 'DENY');
        }

        return $response;
    }
}
