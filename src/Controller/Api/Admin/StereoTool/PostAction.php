<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\StereoTool;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

class PostAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment,
        StereoTool $stereoTool
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $binaryPath = $stereoTool->getBinaryPath();
        if (is_file($binaryPath)) {
            unlink($binaryPath);
        }

        $flowResponse->moveTo($binaryPath);

        return $response->withJson(Entity\Api\Status::success());
    }
}
