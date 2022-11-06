<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class IndexAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();
        $view = $request->getView();

        // Remove the sidebar on the homepage.
        $view->addData(['sidebar' => null]);

        $view = $request->getView();
        $viewData = $view->getData();

        return $view->renderVuePage(
            response: $response,
            component: 'Vue_AdminIndex',
            id: 'admin-index',
            title: __('Administration'),
            props: [
                'adminPanels' => $viewData['admin_panels'] ?? [],
                'statsUrl' => $router->named('api:admin:server:stats'),
                'servicesUrl' => $router->named('api:admin:services'),
            ]
        );
    }
}
