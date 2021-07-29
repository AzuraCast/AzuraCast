<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetFactory;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetCustomAssetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment,
        string $type
    ): ResponseInterface {
        $customAsset = AssetFactory::createForType($environment, $type);

        return $response->withJson(
            [
                'is_uploaded' => $customAsset->isUploaded(),
                'url' => $customAsset->getUrl(),
            ]
        );
    }
}
