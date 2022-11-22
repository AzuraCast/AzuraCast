<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\AssetTypes;
use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DeleteCustomAssetAction
{
    public function __construct(
        private readonly Environment $environment
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type
    ): ResponseInterface {
        $customAsset = AssetTypes::from($type)->createObject($this->environment);
        $customAsset->delete();

        return $response->withJson(Entity\Api\Status::success());
    }
}
