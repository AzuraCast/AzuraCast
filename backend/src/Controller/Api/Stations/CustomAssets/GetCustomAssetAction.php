<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\CustomAssets;

use App\Assets\AssetTypes;
use App\Controller\Api\Admin\CustomAssets\AbstractCustomAssetAction;
use App\Entity\Api\UploadedRecordStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/custom_assets/{type}',
    operationId: 'getStationCustomAsset',
    summary: 'Get the details of the custom asset of the specified type.',
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
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: UploadedRecordStatus::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class GetCustomAssetAction extends AbstractCustomAssetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $customAsset = $this->customAssetFactory->getForType(Types::string($params['type']));
        $station = $request->getStation();

        return $response->withJson(
            new UploadedRecordStatus(
                $customAsset->isUploaded($station),
                $customAsset->getUrl($station),
            )
        );
    }
}
