<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\CustomAssets;

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
        string $station_id,
        string $type
    ): ResponseInterface {
        $customAsset = AssetTypes::from($type)->createObject(
            $this->environment,
            $request->getStation()
        );
        $customAsset->delete();

        return $response->withJson(Entity\Api\Status::success());
    }
}
