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
use App\OpenApi;
use App\Radio\Backend\Liquidsoap;
use OpenApi\Attributes as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

#[
    OA\Put(
        path: '/station/{station_id}/liquidsoap-config',
        operationId: 'putStationLiquidsoapConfig',
        summary: 'Save the editable sections of the station Liquidsoap configuration.',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            // TODO: API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
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

        $backendConfig = $station->backend_config;
        foreach (StationBackendConfiguration::getCustomConfigurationSections() as $field) {
            if (isset($body[$field])) {
                $backendConfig->setCustomConfigurationSection($field, $body[$field]);
            }
        }

        $station->backend_config = $backendConfig;

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
