<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\GeoLite;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use App\Sync\Task\UpdateGeoLiteTask;
use Psr\Http\Message\ResponseInterface;

class PostAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo,
        UpdateGeoLiteTask $geoLiteTask
    ): ResponseInterface {
        $newKey = trim($request->getParsedBodyParam('geolite_license_key', ''));

        $settings = $settingsRepo->readSettings();
        $settings->setGeoliteLicenseKey($newKey);
        $settingsRepo->writeSettings($settings);

        if (!empty($newKey)) {
            $geoLiteTask->updateDatabase($newKey);
            $version = GeoLite::getVersion();
        } else {
            @unlink(GeoLite::getDatabasePath());
            $version = null;
        }

        return $response->withJson([
            'success' => true,
            'version' => $version,
        ]);
    }
}
