<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\StereoTool;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class PostAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $binaryPath = StereoTool::getBinaryPath();
        if (is_file($binaryPath)) {
            unlink($binaryPath);
        }

        $flowResponse->moveTo($binaryPath);

        chmod($binaryPath, 0744);

        if (!StereoTool::getVersion()) {
            @unlink($binaryPath);
            return $response->withStatus(400)->withJson(
                new Entity\Api\Error(400, __('Invalid binary uploaded.'))
            );
        }

        return $response->withJson(Entity\Api\Status::success());
    }
}
