<?php

declare(strict_types=1);

namespace App\Controller;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Enums\StationFeatures;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class StationsAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $view = $request->getView();
        $router = $request->getRouter();

        $globalProps = $view->getGlobalProps();

        $globalProps->set('station', [
            'id' => $station->getIdRequired(),
            'name' => $station->getName(),
            'isEnabled' => $station->getIsEnabled(),
            'hasStarted' => $station->getHasStarted(),
            'needsRestart' => $station->getNeedsRestart(),
            'shortName' => $station->getShortName(),
            'timezone' => $station->getTimezone(),
            'offlineText' => $station->getBrandingConfig()->offline_text,
            'maxBitrate' => $station->getMaxBitrate(),
            'maxMounts' => $station->getMaxMounts(),
            'maxHlsStreams' => $station->getMaxHlsStreams(),
            'enablePublicPages' => $station->getEnablePublicPage(),
            'publicPageUrl' => $router->named('public:index', ['station_id' => $station->getShortName()]),
            'enableOnDemand' => $station->getEnableOnDemand(),
            'onDemandUrl' => $router->named('public:ondemand', ['station_id' => $station->getShortName()]),
            'webDjUrl' => (string)($router->namedAsUri(
                routeName: 'public:dj',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            )->withScheme('https')),
            'enableRequests' => $station->getEnableRequests(),
            'features' => [
                'media' => StationFeatures::Media->supportedForStation($station),
                'sftp' => StationFeatures::Sftp->supportedForStation($station),
                'podcasts' => StationFeatures::Podcasts->supportedForStation($station),
                'streamers' => StationFeatures::Streamers->supportedForStation($station),
                'webhooks' => StationFeatures::Webhooks->supportedForStation($station),
                'mountPoints' => StationFeatures::MountPoints->supportedForStation($station),
                'hlsStreams' => StationFeatures::HlsStreams->supportedForStation($station),
                'remoteRelays' => StationFeatures::RemoteRelays->supportedForStation($station),
                'customLiquidsoapConfig' => StationFeatures::CustomLiquidsoapConfig->supportedForStation($station),
                'autoDjQueue' => $station->supportsAutoDjQueue(),
            ],
        ]);

        return $view->renderVuePage(
            response: $response,
            component: 'Stations',
            id: 'stations-index',
            title: $station->getName(),
            props: [
                'baseUrl' => $router->named('stations:index:index', [
                    'station_id' => $station->getIdRequired(),
                ]),
            ]
        );
    }
}
