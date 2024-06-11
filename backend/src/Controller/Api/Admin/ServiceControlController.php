<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Api\Status;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\ServiceControl;
use Psr\Http\Message\ResponseInterface;

final class ServiceControlController
{
    public function __construct(
        private readonly ServiceControl $serviceControl
    ) {
    }

    public function getAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        $canRestart = $request->getAcl()->isAllowed(GlobalPermissions::All);

        $result = [];
        foreach ($this->serviceControl->getServices() as $service) {
            $row = $service->toArray();

            $row['links'] = [];

            if ($canRestart) {
                $row['links']['restart'] = $router->fromHere(
                    'api:admin:services:restart',
                    ['service' => $service->name]
                );
            }

            $result[] = $row;
        }

        return $response->withJson($result);
    }

    public function restartAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $service */
        $service = $params['service'];

        $this->serviceControl->restart($service);

        return $response->withJson(Status::success());
    }
}
