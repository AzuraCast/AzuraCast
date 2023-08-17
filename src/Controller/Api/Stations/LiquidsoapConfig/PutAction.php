<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\StationBackendConfiguration;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class PutAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Liquidsoap $liquidsoap,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $body = (array)$request->getParsedBody();

        $station = $this->em->refetch($request->getStation());

        $backendConfig = $station->getBackendConfig();
        foreach (StationBackendConfiguration::getCustomConfigurationSections() as $field) {
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
            return $response->withStatus(500)->withJson(Error::fromException($e));
        }

        return $response->withJson(Status::updated());
    }
}
