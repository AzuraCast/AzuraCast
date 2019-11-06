<?php
namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Exception;
use Psr\Http\Message\ResponseInterface;

class PlaylistsController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $backend = $request->getStationBackend();
        if (!$backend::supportsMedia()) {
            throw new Exception(__('This feature is not currently supported on this station.'));
        }

        return $request->getView()->renderToResponse($response, 'stations/playlists/index', [
            'station_tz' => $station->getTimezone(),
        ]);
    }
}