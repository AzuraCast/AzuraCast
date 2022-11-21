<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\StationBackendConfiguration;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

final class EditLiquidsoapConfigAction
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $configSections = StationBackendConfiguration::getCustomConfigurationSections();
        $tokens = Liquidsoap\ConfigWriter::getDividerString();

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
            } else {
                $areas[] = [
                    'is_field' => false,
                    'markup' => $tok,
                ];
            }

            $tok = strtok($tokens);
        }

        $router = $request->getRouter();
        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsLiquidsoapConfig',
            id: 'station-liquidsoap-config',
            title: __('Edit Liquidsoap Configuration'),
            props: [
                'settingsUrl' => $router->fromHere('api:stations:liquidsoap-config'),
                'restartStatusUrl' => $router->fromHere('api:stations:restart-status'),
                'config' => $areas,
                'sections' => $configSections,
            ],
        );
    }
}
