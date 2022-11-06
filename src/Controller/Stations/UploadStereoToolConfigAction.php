<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class UploadStereoToolConfigAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $backendConfig = $request->getStation()->getBackendConfig();
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsStereoToolConfig',
            id: 'stations-stereo-tool-config',
            title: __('Upload Stereo Tool Configuration'),
            props: [
                'restartStatusUrl' => $router->fromHere('api:stations:restart-status'),
                'recordHasStereoToolConfiguration' => !empty($backendConfig->getStereoToolConfigurationPath()),
                'apiUrl' => $router->fromHere('api:stations:stereo_tool_config'),
            ],
        );
    }
}
