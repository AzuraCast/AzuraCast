<?php

declare(strict_types=1);

namespace App\Middleware\Module;

use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\Urls;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Handle API calls and wrap exceptions in JSON formatting.
 */
final class Api
{
    public function __construct(
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        private readonly Environment $environment
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->environment->isDevelopment()) {
            $cloner = new VarCloner();
            $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

            $dumper = new CliDumper('php://output');
            VarDumper::setHandler(
                static function ($var) use ($cloner, $dumper): void {
                    $dumper->dump($cloner->cloneVar($var));
                }
            );
        }

        // Attempt API key auth if a key exists.
        $apiUser = $request->getAttribute(ServerRequest::ATTR_USER);

        // Set default cache control for API pages.
        $settings = $this->settingsRepo->readSettings();

        $preferBrowserUrl = $settings->getPreferBrowserUrl();

        $response = $handler->handle($request);

        // Check for a user-set CORS header override.
        $acaoHeader = trim($settings->getApiAccessControl());
        if (!empty($acaoHeader)) {
            if ('*' === $acaoHeader) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            } else {
                // Return the proper ACAO header matching the origin (if one exists).
                $origin = $request->getHeaderLine('Origin');

                if (!empty($origin)) {
                    $rawOrigins = array_map('trim', explode(',', $acaoHeader));

                    $baseUrl = $settings->getBaseUrl();
                    if (null !== $baseUrl) {
                        $rawOrigins[] = $baseUrl;
                    }

                    $origins = [];
                    foreach ($rawOrigins as $rawOrigin) {
                        $uri = Urls::tryParseUserUrl(
                            $rawOrigin,
                            'System Setting Access-Control-Allowo-Origin'
                        );
                        if (null !== $uri) {
                            if (empty($uri->getScheme())) {
                                $origins[] = (string)($uri->withScheme('http'));
                                $origins[] = (string)($uri->withScheme('https'));
                            } else {
                                $origins[] = (string)$uri;
                            }
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
        } elseif ($apiUser instanceof Entity\User || in_array($request->getMethod(), ['GET', 'OPTIONS'])) {
            // Default behavior:
            // Only set global CORS for GET requests and API-authenticated requests;
            // Session-authenticated, non-GET requests should only be made in a same-host situation.
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        if ($response instanceof Response && !$response->hasCacheLifetime()) {
            if ($preferBrowserUrl || $request->getAttribute(ServerRequest::ATTR_USER) instanceof Entity\User) {
                $response = $response->withNoCache();
            } else {
                $response = $response->withCacheLifetime(15);
            }
        }

        return $response;
    }
}
