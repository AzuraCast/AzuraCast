<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DashboardAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $settings = $this->readSettings();

        // Detect current analytics level.
        $showCharts = $settings->isAnalyticsEnabled();

        $router = $request->getRouter();
        $acl = $request->getAcl();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Dashboard',
            id: 'dashboard',
            title: __('Dashboard'),
            props: [
                'userUrl' => $router->named('api:frontend:account:me'),
                'profileUrl' => $router->named('profile:index'),
                'adminUrl' => $router->named('admin:index:index'),
                'showAdmin' => $acl->isAllowed(GlobalPermissions::View),
                'notificationsUrl' => $router->named('api:frontend:dashboard:notifications'),
                'showCharts' => $showCharts,
                'chartsUrl' => $router->named('api:frontend:dashboard:charts'),
                'manageStationsUrl' => $router->named('admin:stations:index'),
                'stationsUrl' => $router->named('api:frontend:dashboard:stations'),
                'showAlbumArt' => !$settings->getHideAlbumArt(),
            ]
        );
    }
}
