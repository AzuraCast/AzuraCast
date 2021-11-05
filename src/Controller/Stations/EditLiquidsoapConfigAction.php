<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class EditLiquidsoapConfigAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $station = $request->getStation();

        $backend = $request->getStationBackend();
        if (!($backend instanceof Liquidsoap)) {
            throw new StationUnsupportedException();
        }

        $configSections = Liquidsoap\ConfigWriter::getCustomConfigurationSections();
        $config = $backend->getEditableConfiguration($station);
        $tokens = Liquidsoap\ConfigWriter::getDividerString();

        $areas = [];

        $tok = strtok($config, $tokens);
        $i = 0;
        while ($tok !== false) {
            $tok = trim($tok);
            $i++;

            if (in_array($tok, $configSections, true)) {
                $areas[] = [
                    'is_field'   => true,
                    'field_name' => $tok,
                ];
            } else {
                $areas[] = [
                    'is_field' => false,
                    'markup'   => $tok,
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
                'settingsUrl' => (string)$router->fromHere('api:stations:liquidsoap-config'),
                'config'      => $areas,
                'sections'    => $configSections,
            ],
        );
    }
}
