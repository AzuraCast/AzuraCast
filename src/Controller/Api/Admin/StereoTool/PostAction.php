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
    public function __construct(
        private readonly StereoTool $stereoTool
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $binaryPath = $this->stereoTool->getBinaryPath();
        if (is_file($binaryPath)) {
            unlink($binaryPath);
        }

        $flowResponse->moveTo($binaryPath);

        chmod($binaryPath, 0744);

        return $response->withJson(Entity\Api\Status::success());
    }
}
