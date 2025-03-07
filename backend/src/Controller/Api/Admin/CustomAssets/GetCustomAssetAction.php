<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetTypes;
use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\UploadedRecordStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/admin/custom_assets/{type}',
    operationId: 'getAdminCustomAsset',
    description: 'Get the details of the custom asset of the specified type.',
    tags: [OpenApi::TAG_ADMIN],
    parameters: [
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
final class GetCustomAssetAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $type */
        $type = $params['type'];

        $customAsset = AssetTypes::from($type)->createObject($this->environment);

        return $response->withJson(
            new UploadedRecordStatus(
                $customAsset->isUploaded(),
                $customAsset->getUrl()
            )
        );
    }
}
