<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\StereoTool;
use Psr\Http\Message\ResponseInterface;

class GetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        StereoTool $stereoTool,
    ): ResponseInterface {
        return $response->withJson(
            [
                'success' => true,
                'version' => $stereoTool->getVersion(),
            ]
        );
    }
}
