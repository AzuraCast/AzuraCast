<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use Psr\Http\Message\ResponseInterface;

final class GetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $backendConfig = $request->getStation()->getBackendConfig();

        $return = [];
        foreach (ConfigWriter::getCustomConfigurationSections() as $field) {
            $return[$field] = $backendConfig[$field] ?? null;
        }

        return $response->withJson($return);
    }
}
