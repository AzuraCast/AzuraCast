<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\CustomAssets;

use App\Assets\AssetTypes;
use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/custom_assets/{type}',
    operationId: 'deleteStationCustomAsset',
    summary: 'Removes the custom asset of the specified type.',
    tags: [OpenApi::TAG_STATIONS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'type',
            description: 'Asset Type',
            in: 'path',
            required: true,
            schema: new OA\Schema(
                type: 'string',
                enum: AssetTypes::class
            )
        ),
    ],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class DeleteCustomAssetAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $type */
        $type = $params['type'];

        $customAsset = AssetTypes::from($type)->createObject(
            $this->environment,
            $request->getStation()
        );
        $customAsset->delete();

        return $response->withJson(Status::success());
    }
}
