<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Entity\StationBackendConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $backendConfig = $request->getStation()->getBackendConfig();

        $return = [];
        foreach (StationBackendConfiguration::getCustomConfigurationSections() as $field) {
            $return[$field] = $backendConfig->getCustomConfigurationSection($field);
        }

        return $response->withJson($return);
    }
}
