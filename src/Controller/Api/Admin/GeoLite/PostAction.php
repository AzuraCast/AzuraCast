<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\GeoLite;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use App\Sync\Task\UpdateGeoLiteTask;
use Psr\Http\Message\ResponseInterface;

final class PostAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly UpdateGeoLiteTask $geoLiteTask
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $newKey = trim($request->getParsedBodyParam('geolite_license_key', ''));

        $settings = $this->readSettings();
        $settings->setGeoliteLicenseKey($newKey);
        $this->writeSettings($settings);

        if (!empty($newKey)) {
            $this->geoLiteTask->updateDatabase($newKey);
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
