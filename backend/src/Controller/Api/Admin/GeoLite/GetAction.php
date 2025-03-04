<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\GeoLite;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\GeoLiteStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\IpGeolocator\GeoLite;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/admin/geolite',
    operationId: 'getGeoLite',
    description: 'Get the current MaxMindDB GeoLite Database status.',
    tags: ['Administration: General'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(
                ref: GeoLiteStatus::class
            )
        ),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
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
            new GeoLiteStatus(
                $version,
                $settings->getGeoliteLicenseKey()
            )
        );
    }
}
