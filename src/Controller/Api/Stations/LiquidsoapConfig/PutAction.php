<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use Psr\Http\Message\ResponseInterface;

class PutAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        ReloadableEntityManagerInterface $em
    ): ResponseInterface {
        $body = (array)$request->getParsedBody();

        $station = $em->refetch($request->getStation());

        $backendConfig = $station->getBackendConfig();
        foreach (ConfigWriter::getCustomConfigurationSections() as $field) {
            if (isset($body[$field])) {
                $backendConfig->set($field, $body[$field]);
            }
        }

        $station->setBackendConfig($backendConfig);

        $em->persist($station);
        $em->flush();

        return $response->withJson(Entity\Api\Status::updated());
    }
}
