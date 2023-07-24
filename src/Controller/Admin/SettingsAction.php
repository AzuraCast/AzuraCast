<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\SettingsComponent;
use Psr\Http\Message\ResponseInterface;

final class SettingsAction implements SingleActionInterface
{
    public function __construct(
        private readonly SettingsComponent $settingsComponent
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin/Settings',
            id: 'admin-settings',
            title: __('System Settings'),
            props: $this->settingsComponent->getProps($request),
        );
    }
}
