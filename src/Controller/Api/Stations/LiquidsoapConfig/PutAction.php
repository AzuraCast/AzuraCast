<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class PutAction
{
    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Liquidsoap $liquidsoap,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $body = (array)$request->getParsedBody();

        $station = $this->em->refetch($request->getStation());

        $backendConfig = $station->getBackendConfig();
        foreach (Entity\StationBackendConfiguration::getCustomConfigurationSections() as $field) {
            if (isset($body[$field])) {
                $backendConfig->setCustomConfigurationSection($field, $body[$field]);
            }
        }

        $station->setBackendConfig($backendConfig);

        $this->em->persist($station);
        $this->em->flush();

        try {
            $event = new WriteLiquidsoapConfiguration($station, false, false);
            $this->eventDispatcher->dispatch($event);

            $config = $event->buildConfiguration();
            $this->liquidsoap->verifyConfig($config);
        } catch (Throwable $e) {
            return $response->withStatus(500)->withJson(Entity\Api\Error::fromException($e));
        }

        return $response->withJson(Entity\Api\Status::updated());
    }
}
