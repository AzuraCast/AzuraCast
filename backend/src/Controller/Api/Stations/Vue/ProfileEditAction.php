<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\StationFormComponent;
use Psr\Http\Message\ResponseInterface;

final class ProfileEditAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationFormComponent $stationFormComponent
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson($this->stationFormComponent->getProps($request));
    }
}
