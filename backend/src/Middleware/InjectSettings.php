<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\Repository\SettingsRepository;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject core services into the request object for use further down the stack.
 */
final class InjectSettings extends AbstractMiddleware
{
    public function __construct(
        private readonly SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $settings = $this->settingsRepo->readSettings();

        $request = $request->withAttribute(ServerRequest::ATTR_SETTINGS, $settings);

        return $handler->handle($request);
    }
}
