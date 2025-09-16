<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Controller\SingleActionInterface;
use App\Entity\StationBackendConfiguration;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use OpenApi\Attributes as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/liquidsoap-config',
        operationId: 'getStationLiquidsoapConfig',
        summary: 'Get the generated and editable sections of the station Liquidsoap configuration.',
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
final readonly class GetAction implements SingleActionInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $configSections = StationBackendConfiguration::getCustomConfigurationSections();
        $tokens = ConfigWriter::getDividerString();

        $event = new WriteLiquidsoapConfiguration($station, true, false);
        $this->eventDispatcher->dispatch($event);
        $config = $event->buildConfiguration();

        $areas = [];

        $tok = strtok($config, $tokens);
        while ($tok !== false) {
            $tok = trim($tok);
            if (in_array($tok, $configSections, true)) {
                $areas[] = [
                    'is_field' => true,
                    'field_name' => $tok,
                ];
            } elseif (!empty($tok)) {
                $areas[] = [
                    'is_field' => false,
                    'markup' => $tok,
                ];
            }

            $tok = strtok($tokens);
        }

        $backendConfig = $request->getStation()->backend_config;

        $contents = [];
        foreach ($configSections as $field) {
            $contents[$field] = $backendConfig->getCustomConfigurationSection($field);
        }

        return $response->withJson([
            'config' => $areas,
            'sections' => $configSections,
            'contents' => $contents,
        ]);
    }
}
