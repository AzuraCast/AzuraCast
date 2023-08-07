<?php

declare(strict_types=1);

namespace App\Middleware\Module;

use App\Container\SettingsAwareTrait;
use App\Event;
use App\Http\ServerRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

/**
 * Module middleware for the /admin pages.
 */
final class Admin
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $settings = $this->readSettings();

        $event = new Event\BuildAdminMenu($request, $settings);
        $this->dispatcher->dispatch($event);

        $view = $request->getView();

        $activeTab = null;
        $currentRoute = RouteContext::fromRequest($request)->getRoute();

        if ($currentRoute instanceof RouteInterface) {
            $routeParts = explode(':', $currentRoute->getName() ?? '');
            $activeTab = $routeParts[1];
        }

        $globalProps = $view->getGlobalProps();

        $router = $request->getRouter();

        $globalProps->set('sidebarProps', [
            'homeUrl' => $router->named('admin:index:index'),
            'menu' => $event->getFilteredMenu(),
            'active' => $activeTab,
        ]);

        return $handler->handle($request);
    }
}
