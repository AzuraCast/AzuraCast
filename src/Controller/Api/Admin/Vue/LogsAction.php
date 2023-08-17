<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationRepository;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class LogsAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        $acl = $request->getAcl();
        $stationLogs = [];
        foreach ($this->stationRepo->iterateEnabledStations() as $station) {
            if ($acl->isAllowed(StationPermissions::Logs, $station)) {
                $stationLogs[] = [
                    'id' => $station->getIdRequired(),
                    'name' => $station->getName(),
                    'url' => $router->named('api:stations:logs', [
                        'station_id' => $station->getIdRequired(),
                    ]),
                ];
            }
        }

        return $response->withJson([
            'systemLogsUrl' => $router->fromHere('api:admin:logs'),
            'stationLogs' => $stationLogs,
        ]);
    }
}
