<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetFactory;
use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DeleteCustomAssetAction
{
    public function __construct(
        private readonly Environment $environment,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type
    ): ResponseInterface {
        $customAsset = AssetFactory::createForType($this->environment, $type);

        $customAsset->delete();

        return $response->withJson(Entity\Api\Status::success());
    }
}
