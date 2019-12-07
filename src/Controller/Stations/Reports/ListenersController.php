<?php
namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ListenersController
{
    protected Entity\Repository\SettingsRepository $settingsRepo;

    public function __construct(Entity\Repository\SettingsRepository $settingsRepo)
    {
        $this->settingsRepo = $settingsRepo;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $view = $request->getView();

        $analytics_level = $this->settingsRepo->getSetting(Entity\Settings::LISTENER_ANALYTICS,
            Entity\Analytics::LEVEL_ALL);

        if ($analytics_level !== Entity\Analytics::LEVEL_ALL) {
            return $view->renderToResponse($response, 'stations/reports/restricted');
        }

        return $view->renderToResponse($response, 'stations/reports/listeners');
    }
}
