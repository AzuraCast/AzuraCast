<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class StereoToolAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminStereoTool',
            id: 'admin-stereo-tool',
            title: __('Install Stereo Tool'),
            props: [
                'apiUrl' => $router->named('api:admin:stereo_tool'),
            ],
        );
    }
}
