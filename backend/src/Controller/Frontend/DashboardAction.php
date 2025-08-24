<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DashboardAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Dashboard',
            id: 'dashboard',
            title: __('Dashboard')
        );
    }
}
