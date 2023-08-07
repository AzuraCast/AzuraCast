<?php

declare(strict_types=1);

namespace App\Middleware\Module;

use App\Container\SettingsAwareTrait;
use App\Enums\StationPermissions;
use App\Event;
use App\Http\ServerRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

/**
 * Module middleware for the /station pages.
 */
final class Stations
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $request->getView();

        $station = $request->getStation();
        $view->addData(
            [
                'station' => $station,
            ]
        );

        $settings = $this->readSettings();

        $event = new Event\BuildStationMenu($station, $request, $settings);
        $this->dispatcher->dispatch($event);

        $activeTab = null;
        $currentRoute = RouteContext::fromRequest($request)->getRoute();
        if ($currentRoute instanceof RouteInterface) {
            $routeParts = explode(':', $currentRoute->getName() ?? '');
            $activeTab = $routeParts[1];
        }

        $globalProps = $view->getGlobalProps();

        $globalProps->set('station', [
            'id' => $station->getIdRequired(),
            'name' => $station->getName(),
            'shortName' => $station->getShortName(),
            'timezone' => $station->getTimezone(),
        ]);

        $router = $request->getRouter();
        $acl = $request->getAcl();

        $globalProps->set('sidebarProps', [
            'profileUrl' => $router->fromHere('stations:profile:index'),
            'editProfileUrl' => $router->fromHere('stations:profile:edit'),
            'showEditProfile' => $acl->isAllowed(StationPermissions::Profile, $station),
            'menu' => $event->getFilteredMenu(),
            'active' => $activeTab,
        ]);

        return $handler->handle($request);
    }
}
