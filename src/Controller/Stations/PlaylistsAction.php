<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlaylistsAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Playlists',
            id: 'station-playlist',
            title: __('Playlists'),
            props: [
                'useManualAutoDj' => $station->useManualAutoDJ(),
            ],
        );
    }
}
