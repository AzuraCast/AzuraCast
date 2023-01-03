<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\CustomAssets;

use App\Assets\AssetTypes;
use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\AlbumArt;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class PostCustomAssetAction
{
    public function __construct(
        private readonly Environment $environment
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $type
    ): ResponseInterface {
        $customAsset = AssetTypes::from($type)->createObject(
            $this->environment,
            $request->getStation()
        );

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
