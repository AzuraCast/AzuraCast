<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\StationRepository;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

final class HlsStreamsAction
{
    public function __construct(
        private readonly StationRepository $stationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend->supportsHls()) {
            throw new StationUnsupportedException();
        }

        $view = $request->getView();

        if (!$station->getEnableHls()) {
            $params = $request->getQueryParams();
            if (isset($params['enable'])) {
                $station->setEnableHls(true);

                $em = $this->stationRepo->getEntityManager();
                $em->persist($station);
                $em->flush();

                $this->stationRepo->resetHls($station, $request->getStationBackend());

                $request->getFlash()->addMessage(
                    '<b>' . __('HLS enabled!') . '</b>',
                    Flash::SUCCESS
                );

                return $response->withRedirect((string)$request->getRouter()->fromHere('stations:hls:index'));
            }

            return $view->renderToResponse($response, 'stations/hls/disabled');
        }

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsHlsStreams',
            id: 'station-hls-streams',
            title: __('HLS Streams'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:hls_streams'),
                'restartStatusUrl' => (string)$router->fromHere('api:stations:restart-status'),
            ],
        );
    }
}
