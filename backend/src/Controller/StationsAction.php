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
            'id' => $station->id,
            'name' => $station->name,
            'isEnabled' => $station->is_enabled,
            'hasStarted' => $station->has_started,
            'needsRestart' => $station->needs_restart,
            'shortName' => $station->short_name,
            'timezone' => $station->timezone,
            'offlineText' => $station->branding_config->offline_text,
            'maxBitrate' => $station->max_bitrate,
            'maxMounts' => $station->max_mounts,
            'maxHlsStreams' => $station->max_hls_streams,
            'enablePublicPages' => $station->enable_public_page,
            'publicPageUrl' => $router->named('public:index', ['station_id' => $station->short_name]),
            'enableOnDemand' => $station->enable_on_demand,
            'onDemandUrl' => $router->named('public:ondemand', ['station_id' => $station->short_name]),
            'webDjUrl' => (string)($router->namedAsUri(
                routeName: 'public:dj',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            )->withScheme('https')),
            'enableRequests' => $station->enable_requests,
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
            title: $station->name,
            props: [
                'baseUrl' => $router->named('stations:index:index', [
                    'station_id' => $station->id,
                ]),
            ]
        );
    }
}
