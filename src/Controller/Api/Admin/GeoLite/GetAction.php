<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\GeoLite;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use Psr\Http\Message\ResponseInterface;

final class GetAction
{
    public function __construct(
        private readonly SettingsRepository $settingsRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $version = GeoLite::getVersion();
        $settings = $this->settingsRepo->readSettings();

        return $response->withJson(
            [
                'success' => true,
                'version' => $version,
                'key' => $settings->getGeoliteLicenseKey(),
            ]
        );
    }
}
