<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetTypes;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetCustomAssetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type
    ): ResponseInterface {
        $customAsset = AssetTypes::from($type)->createObject();

        return $response->withJson(
            [
                'is_uploaded' => $customAsset->isUploaded(),
                'url' => $customAsset->getUrl(),
            ]
        );
    }
}
