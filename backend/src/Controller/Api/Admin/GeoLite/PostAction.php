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
use App\Sync\Task\UpdateGeoLiteTask;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/admin/geolite',
    operationId: 'postGeoLite',
    summary: 'Set the GeoLite MaxMindDB Database license key.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'key',
                    type: 'string',
                    nullable: true,
                ),
            ]
        )
    ),
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: GeoLiteStatus::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
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
        $body = (array)$request->getParsedBody();
        $newKey = Types::stringOrNull($body['key'] ?? $body['geolite_license_key'] ?? null, true);

        $settings = $this->readSettings();
        $settings->geolite_license_key = $newKey;
        $this->writeSettings($settings);

        if (!empty($newKey)) {
            $this->geoLiteTask->updateDatabase($newKey);
            $version = GeoLite::getVersion();
        } else {
            @unlink(GeoLite::getDatabasePath());
            $version = null;
        }

        return $response->withJson(
            new GeoLiteStatus(
                $version,
                $newKey
            )
        );
    }
}
