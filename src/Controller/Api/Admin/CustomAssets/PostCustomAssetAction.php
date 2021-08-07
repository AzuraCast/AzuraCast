<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetFactory;
use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Intervention\Image\ImageManager;
use Psr\Http\Message\ResponseInterface;

class PostCustomAssetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment,
        ImageManager $imageManager,
        string $type
    ): ResponseInterface {
        $customAsset = AssetFactory::createForType($environment, $type);

        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $imageContents = $flowResponse->readAndDeleteUploadedFile();
        $image = $imageManager->make($imageContents);

        $customAsset->upload($image);

        return $response->withJson(new Entity\Api\Status());
    }
}
