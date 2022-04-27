<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class PutAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        ReloadableEntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        Liquidsoap $liquidsoap,
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

        try {
            $event = new WriteLiquidsoapConfiguration($station, false, false);
            $eventDispatcher->dispatch($event);

            $config = $event->buildConfiguration();
            $liquidsoap->verifyConfig($config);
        } catch (\Throwable $e) {
            return $response->withStatus(500)->withJson(Entity\Api\Error::fromException($e));
        }

        return $response->withJson(Entity\Api\Status::updated());
    }
}
