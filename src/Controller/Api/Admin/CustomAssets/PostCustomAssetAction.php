<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetTypes;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\AlbumArt;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class PostCustomAssetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type
    ): ResponseInterface {
        $customAsset = AssetTypes::from($type)->createObject();

        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $imageContents = $flowResponse->readAndDeleteUploadedFile();
        $customAsset->upload(
            AlbumArt::getImageManager()->make($imageContents)
        );

        return $response->withJson(Entity\Api\Status::success());
    }
}
