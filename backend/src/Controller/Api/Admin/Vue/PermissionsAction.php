<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PermissionsAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $actions = $request->getAcl()->listPermissions();

        return $response->withJson([
            'stations' => $this->stationRepo->fetchSelect(),
            'globalPermissions' => $actions['global'],
            'stationPermissions' => $actions['station'],
        ]);
    }
}
