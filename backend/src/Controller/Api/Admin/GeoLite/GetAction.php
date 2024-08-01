<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\GeoLite;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use Psr\Http\Message\ResponseInterface;

final class GetAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $version = GeoLite::getVersion();
        $settings = $this->readSettings();

        return $response->withJson(
            [
                'success' => true,
                'version' => $version,
                'key' => $settings->getGeoliteLicenseKey(),
            ]
        );
    }
}
