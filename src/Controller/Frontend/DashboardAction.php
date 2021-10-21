<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Acl;
use App\Entity;
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
        // Detect current analytics level.
        $analyticsLevel = $settingsRepo->readSettings()->getAnalytics();
        $showCharts = $analyticsLevel !== Entity\Analytics::LEVEL_NONE;

        // Avatars
        $avatarService = $avatar->getAvatarService();

        $user = $request->getUser();
        $router = $request->getRouter();
        $acl = $request->getAcl();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_Dashboard',
            id: 'dashboard',
            title: __('Dashboard'),
            props: [
                'avatar'            => $avatar->getAvatar($request->getUser()->getEmail(), 64),
                'avatarServiceName' => $avatarService->getServiceName(),
                'avatarServiceUrl'  => $avatarService->getServiceUrl(),
                'userName'          => $user->getName() ?? __('AzuraCast User'),
                'userEmail'         => $user->getEmail(),
                'profileUrl'        => (string)$router->named('profile:index'),
                'adminUrl'          => (string)$router->named('admin:index:index'),
                'showAdmin'         => $acl->isAllowed(Acl::GLOBAL_VIEW),
                'notificationsUrl'  => (string)$router->named('api:frontend:dashboard:notifications'),
                'showCharts'        => $showCharts,
                'chartsUrl'         => (string)$router->named('api:frontend:dashboard:charts'),
                'manageStationsUrl' => (string)$router->named('admin:stations:index'),
                'stationsUrl'       => (string)$router->named('api:frontend:dashboard:stations'),
            ]
        );
    }
}
