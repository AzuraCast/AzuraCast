<?php

namespace App\Controller\Frontend;

use App\Acl;
use App\Entity;
use App\EventDispatcher;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class DashboardAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        EventDispatcher $eventDispatcher,
        Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator,
        Entity\Repository\SettingsRepository $settingsRepo
    ): ResponseInterface {
        $view = $request->getView();
        $acl = $request->getAcl();

        // Detect current analytics level.
        $settings = $settingsRepo->readSettings();
        $analyticsLevel = $settings->getAnalytics();
        $showCharts = $analyticsLevel !== Entity\Analytics::LEVEL_NONE;

        return $view->renderToResponse(
            $response,
            'frontend/index/index',
            [
                'showAdmin' => $acl->isAllowed(Acl::GLOBAL_VIEW),
                'showCharts' => $showCharts,
            ]
        );
    }
}
