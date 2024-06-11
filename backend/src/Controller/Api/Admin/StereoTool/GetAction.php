<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\StereoTool;
use Psr\Http\Message\ResponseInterface;

final class GetAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            [
                'success' => true,
                'version' => StereoTool::getVersion(),
            ]
        );
    }
}
