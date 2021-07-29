<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetFactory;
use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DeleteCustomAssetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment,
        string $type
    ): ResponseInterface {
        $customAsset = AssetFactory::createForType($environment, $type);

        $customAsset->delete();

        return $response->withJson(new Entity\Api\Status());
    }
}
