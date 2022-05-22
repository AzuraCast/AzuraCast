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

final class PostCustomAssetAction
{
    public function __construct(
        private readonly Environment $environment,
        private readonly ImageManager $imageManager,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type
    ): ResponseInterface {
        $customAsset = AssetFactory::createForType($this->environment, $type);

        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $imageContents = $flowResponse->readAndDeleteUploadedFile();
        $image = $this->imageManager->make($imageContents);

        $customAsset->upload($image);

        return $response->withJson(Entity\Api\Status::success());
    }
}
