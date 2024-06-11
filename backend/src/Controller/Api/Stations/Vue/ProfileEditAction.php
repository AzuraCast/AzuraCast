<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\StationFormComponent;
use Psr\Http\Message\ResponseInterface;

final class ProfileEditAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationFormComponent $stationFormComponent
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        return $response->withJson(
            array_merge(
                $this->stationFormComponent->getProps($request),
                [
                    'editUrl' => $router->fromHere('api:stations:profile:edit'),
                ]
            )
        );
    }
}
