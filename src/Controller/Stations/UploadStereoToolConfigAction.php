<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class UploadStereoToolConfigAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $backendConfig = $request->getStation()->getBackendConfig();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/StereoToolConfig',
            id: 'stations-stereo-tool-config',
            title: __('Upload Stereo Tool Configuration'),
            props: [
                'recordHasStereoToolConfiguration' => !empty($backendConfig->getStereoToolConfigurationPath()),
            ],
        );
    }
}
