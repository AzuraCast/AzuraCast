<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Vue\StationGlobalFeatures;
use App\Entity\Api\Vue\StationGlobals;
use App\Enums\StationFeatures;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocation;
use Psr\Http\Message\ResponseInterface;

final class GetDashboardAction implements SingleActionInterface
{
    public function __construct(
        private IpGeolocation $ipGeolocation
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $settings = $request->getSettings();
        $station = $request->getStation();
        $router = $request->getRouter();

        $backendConfig = $station->backend_config;

        $result = new StationGlobals(
            id: $station->id,
            name: $station->name,
            shortName: $station->short_name,
            description: $station->description,
            isEnabled: $station->is_enabled,
            hasStarted: $station->has_started,
            needsRestart: $station->needs_restart,
            timezone: $station->timezone,
            offlineText: $station->branding_config->offline_text,
            maxBitrate: $station->max_bitrate,
            maxMounts: $station->max_mounts,
            maxHlsStreams: $station->max_hls_streams,
            enablePublicPages: $station->enable_public_page,
            publicPageUrl: $router->named(
                routeName: 'public:index',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            enableOnDemand: $station->enable_on_demand,
            onDemandUrl: $router->named(
                routeName: 'public:ondemand',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            enableStreamers: $station->enable_streamers,
            webDjUrl: (string)($router->namedAsUri(
                routeName: 'public:dj',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            )->withScheme('https')),
            publicPodcastsUrl: $router->named(
                routeName: 'public:podcasts',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            publicScheduleUrl: $router->named(
                routeName: 'public:schedule',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            enableRequests: $station->enable_requests,
            features: new StationGlobalFeatures(
                media: StationFeatures::Media->supportedForStation($station, $settings),
                sftp: StationFeatures::Sftp->supportedForStation($station, $settings),
                podcasts: StationFeatures::Podcasts->supportedForStation($station, $settings),
                streamers: StationFeatures::Streamers->supportedForStation($station, $settings),
                webhooks: StationFeatures::Webhooks->supportedForStation($station, $settings),
                requests: StationFeatures::Requests->supportedForStation($station, $settings),
                mountPoints: StationFeatures::MountPoints->supportedForStation($station, $settings),
                hlsStreams: StationFeatures::HlsStreams->supportedForStation($station, $settings),
                remoteRelays: StationFeatures::RemoteRelays->supportedForStation($station, $settings),
                customLiquidsoapConfig: StationFeatures::CustomLiquidsoapConfig->supportedForStation(
                    $station,
                    $settings
                ),
                autoDjQueue: $station->supportsAutoDjQueue(),
            ),
            ipGeoAttribution: $this->ipGeolocation->getAttribution(),
            backendType: $station->backend_type,
            frontendType: $station->frontend_type,
            canReload: $station->frontend_type->supportsReload(),
            useManualAutoDj: $backendConfig->use_manual_autodj
        );

        return $response->withJson($result);
    }
}
