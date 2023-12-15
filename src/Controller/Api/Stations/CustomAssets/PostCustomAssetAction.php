<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\CustomAssets;

use App\Assets\AssetTypes;
use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\AlbumArt;
use App\Media\MimeType;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class PostCustomAssetAction implements SingleActionInterface
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

        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $imageContents = $flowResponse->readAndDeleteUploadedFile();
        $customAsset->upload(
            AlbumArt::getImageManager()->read($imageContents),
            MimeType::getMimeTypeDetector()->detectMimeTypeFromBuffer($imageContents) ?? 'image/jpeg'
        );

        return $response->withJson(Status::success());
    }
}
