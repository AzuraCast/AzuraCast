<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ProfileController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationScheduleRepository $scheduleRepo
    ): ResponseInterface {
        $station = $request->getStation();

        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();
        $remotes = $request->getStationRemotes();

        $apiResponse = new Entity\Api\StationProfile;

        $apiResponse->cache = 'database';
        $apiResponse->station = $station->api(
            $frontend,
            $remotes,
            null,
            true
        );

        $apiResponse->services = new Entity\Api\StationServiceStatus(
            $backend->isRunning($station),
            $frontend->isRunning($station)
        );

        $apiResponse->schedule = $scheduleRepo->getUpcomingSchedule($station);

        // Attempt to merge in NowPlaying data if available.
        $np = $station->getNowplaying();

        if ($np instanceof Entity\Api\NowPlaying) {
            $apiResponse->listeners = $np->listeners;
            $apiResponse->live = $np->live;
            $apiResponse->now_playing = $np->now_playing;
            $apiResponse->playing_next = $np->playing_next;
            $apiResponse->song_history = $np->song_history;
        } else {
            $apiResponse->listeners = new Entity\Api\NowPlayingListeners([]);
            $apiResponse->live = new Entity\Api\NowPlayingLive(null, '');
        }

        $apiResponse->resolveUrls($request->getRouter()->getBaseUrl());
        $apiResponse->update();

        return $response->withJson($apiResponse);
    }
}