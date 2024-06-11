<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Controller\SingleActionInterface;
use App\Entity\StationBackendConfiguration;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

final class GetAction implements SingleActionInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
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

        $backendConfig = $request->getStation()->getBackendConfig();

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
