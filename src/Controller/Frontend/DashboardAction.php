<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Avatar;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class DashboardAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Avatar $avatar,
        Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator,
        Entity\Repository\SettingsRepository $settingsRepo
    ): ResponseInterface {
        $settings = $settingsRepo->readSettings();

        // Detect current analytics level.
        $showCharts = $settings->isAnalyticsEnabled();

        $router = $request->getRouter();
        $acl = $request->getAcl();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_Dashboard',
            id: 'dashboard',
            title: __('Dashboard'),
            props: [
                'userUrl'           => (string)$router->named('api:frontend:account:me'),
                'profileUrl'        => (string)$router->named('profile:index'),
                'adminUrl'          => (string)$router->named('admin:index:index'),
                'showAdmin'         => $acl->isAllowed(GlobalPermissions::View),
                'notificationsUrl'  => (string)$router->named('api:frontend:dashboard:notifications'),
                'showCharts'        => $showCharts,
                'chartsUrl'         => (string)$router->named('api:frontend:dashboard:charts'),
                'manageStationsUrl' => (string)$router->named('admin:stations:index'),
                'stationsUrl'       => (string)$router->named('api:frontend:dashboard:stations'),
                'showAlbumArt'      => !$settings->getHideAlbumArt(),
            ]
        );
    }
}
