<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class BulkMediaAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsBulkMedia',
            id: 'station-bulk-media',
            title: __('Bulk Media Import/Export'),
            props: [
                'apiUrl' => $router->fromHere('api:stations:files:bulk'),
            ],
        );
    }
}
