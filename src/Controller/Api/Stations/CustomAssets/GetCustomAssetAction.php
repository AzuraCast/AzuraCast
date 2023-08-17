<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\CustomAssets;

use App\Assets\AssetTypes;
use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetCustomAssetAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $type */
        $type = $params['type'];

        $customAsset = AssetTypes::from($type)->createObject(
            $this->environment,
            $request->getStation()
        );

        return $response->withJson(
            [
                'is_uploaded' => $customAsset->isUploaded(),
                'url' => $customAsset->getUrl(),
            ]
        );
    }
}
