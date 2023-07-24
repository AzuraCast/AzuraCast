<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class IndexAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();
        $view = $request->getView();

        // Remove the sidebar on the homepage.
        $view->getSections()->unset('sidebar');

        $view = $request->getView();
        $viewData = $view->getData();

        return $view->renderVuePage(
            response: $response,
            component: 'Admin/Index',
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
