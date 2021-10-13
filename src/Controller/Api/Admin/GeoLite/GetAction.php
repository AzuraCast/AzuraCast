<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\GeoLite;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use Psr\Http\Message\ResponseInterface;

class GetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $version = GeoLite::getVersion();
        $settings = $settingsRepo->readSettings();

        return $response->withJson(
            [
                'success' => true,
                'version' => $version,
                'key'     => $settings->getGeoliteLicenseKey(),
            ]
        );
    }
}
