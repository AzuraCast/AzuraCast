<?php

declare(strict_types=1);

namespace App\Middleware\Module;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\User;
use App\Exception\Http\InvalidRequestAttribute;
use App\Exception\WrappedException;
use App\Http\ServerRequest;
use App\Middleware\AbstractMiddleware;
use App\Utilities\Urls;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;

/**
 * Handle API calls and wrap exceptions in JSON formatting.
 */
final class Api extends AbstractMiddleware
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute(ServerRequest::ATTR_IS_API, true);

        try {
            if ($this->environment->isDevelopment()) {
                $this->registerCliDumper();
            }

            $response = $handler->handle($request);

            return $this->setAccessControl($request, $response);
        } catch (Throwable $e) {
            throw new WrappedException($request, $e);
        }
    }

    private function registerCliDumper(): void
    {
        $cloner = new VarCloner();
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $dumper = new CliDumper('php://output');
        VarDumper::setHandler(
            static function ($var) use ($cloner, $dumper): void {
                $dumper->dump($cloner->cloneVar($var));
            }
        );
    }

    private function setAccessControl(
        ServerRequest $request,
        ResponseInterface $response
    ): ResponseInterface {
        try {
            $apiUser = $request->getUser();
        } catch (InvalidRequestAttribute) {
            $apiUser = null;
        }

        $settings = $this->readSettings();

        // Check for a user-set CORS header override.
        $acaoHeader = $settings->api_access_control;
        if (null !== $acaoHeader) {
            if ('*' === $acaoHeader) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            } else {
                // Return the proper ACAO header matching the origin (if one exists).
                $origin = $request->getHeaderLine('Origin');

                if (!empty($origin)) {
                    $rawOrigins = array_map('trim', explode(',', $acaoHeader));

                    $baseUrl = $settings->base_url;
                    if (null !== $baseUrl) {
                        $rawOrigins[] = $baseUrl;
                    }

                    $origins = [];
                    foreach ($rawOrigins as $rawOrigin) {
                        $uri = Urls::tryParseUserUrl(
                            $rawOrigin,
                            'System Setting Access-Control-Allow-Origin'
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
        } elseif ($apiUser instanceof User || in_array($request->getMethod(), ['GET', 'OPTIONS'])) {
            // Default behavior:
            // Only set global CORS for GET requests and API-authenticated requests;
            // Session-authenticated, non-GET requests should only be made in a same-host situation.
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        return $response;
    }
}
