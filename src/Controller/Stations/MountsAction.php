<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class MountsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $station = $request->getStation();

        $settings = $settingsRepo->readSettings();

        return $request->getView()->renderToResponse(
            $response,
            'stations/mounts/index',
            [
                'station' => $station,
                'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
            ]
        );
    }
}
