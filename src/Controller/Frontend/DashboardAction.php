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
        $view = $request->getView();
        $acl = $request->getAcl();

        // Detect current analytics level.
        $analyticsLevel = $settingsRepo->readSettings()->getAnalytics();
        $showCharts = $analyticsLevel !== Entity\Analytics::LEVEL_NONE;

        // Avatars
        $avatarService = $avatar->getAvatarService();

        return $view->renderToResponse(
            $response,
            'frontend/index/index',
            [
                'avatar' => $avatar->getAvatar($request->getUser()->getEmail(), 64),
                'avatarServiceName' => $avatarService->getServiceName(),
                'avatarServiceUrl' => $avatarService->getServiceUrl(),
                'showAdmin' => $acl->isAllowed(Acl::GLOBAL_VIEW),
                'showCharts' => $showCharts,
            ]
        );
    }
}
