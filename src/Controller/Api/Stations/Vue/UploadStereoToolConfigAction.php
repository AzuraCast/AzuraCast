<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

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

        return $response->withJson([
            'recordHasStereoToolConfiguration' => !empty($backendConfig->getStereoToolConfigurationPath()),
        ]);
    }
}
