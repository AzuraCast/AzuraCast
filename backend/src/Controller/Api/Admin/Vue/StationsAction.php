<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\VueComponent\StationFormComponent;
use Psr\Http\Message\ResponseInterface;

final readonly class StationsAction implements SingleActionInterface
{
    public function __construct(
        private StationFormComponent $stationFormComponent,
        private Adapters $adapters
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            array_merge(
                $this->stationFormComponent->getProps($request),
                [
                    'frontendTypes' => $this->adapters->listFrontendAdapters(),
                    'backendTypes' => $this->adapters->listBackendAdapters(),
                ]
            )
        );
    }
}
