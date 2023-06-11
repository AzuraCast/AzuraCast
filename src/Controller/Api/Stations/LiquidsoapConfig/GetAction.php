<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Controller\SingleActionInterface;
use App\Entity\StationBackendConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $backendConfig = $request->getStation()->getBackendConfig();

        $return = [];
        foreach (StationBackendConfiguration::getCustomConfigurationSections() as $field) {
            $return[$field] = $backendConfig->getCustomConfigurationSection($field);
        }

        return $response->withJson($return);
    }
}
