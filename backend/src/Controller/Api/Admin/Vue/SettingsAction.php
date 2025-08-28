<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\SettingsComponent;
use Psr\Http\Message\ResponseInterface;

final readonly class SettingsAction implements SingleActionInterface
{
    public function __construct(
        private SettingsComponent $settingsComponent
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson($this->settingsComponent->getProps($request));
    }
}
