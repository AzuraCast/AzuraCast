<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetTypes;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\AlbumArt;
use App\Media\MimeType;
use App\OpenApi;
use App\Service\Flow;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/admin/custom_assets/{type}',
    operationId: 'postAdminCustomAsset',
    summary: 'Upload a new custom asset of the specified type.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
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
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class PostCustomAssetAction extends AbstractCustomAssetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $customAsset = $this->customAssetFactory->getForType(Types::string($params['type']));

        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $imageContents = $flowResponse->readAndDeleteUploadedFile();

        $customAsset->upload(
            AlbumArt::getImageManager()->read($imageContents),
            MimeType::getMimeTypeDetector()->detectMimeTypeFromBuffer($imageContents) ?? 'image/jpeg'
        );

        return $response->withJson(Status::success());
    }
}
