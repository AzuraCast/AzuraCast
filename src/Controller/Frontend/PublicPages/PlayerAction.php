<?php

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PlayerAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        bool $embed = false
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response->withHeader('X-Frame-Options', '*');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $np = [
            'station' => [
                'listen_url' => '',
                'mounts' => [],
                'remotes' => [],
            ],
            'now_playing' => [
                'song' => [
                    'title' => __('Song Title'),
                    'artist' => __('Song Artist'),
                    'art' => '',
                ],
                'playlist' => '',
                'is_request' => false,
                'duration' => 0,
            ],
            'live' => [
                'is_live' => false,
                'streamer_name' => '',
            ],
            'song_history' => [],
        ];

        $station_np = $station->getNowplaying();
        if ($station_np instanceof Entity\Api\NowPlaying) {
            $station_np->resolveUrls($request->getRouter()->getBaseUrl());
            $np = array_intersect_key($station_np->toArray(), $np) + $np;
        }

        $templateName = ($embed)
            ? 'frontend/public/embed'
            : 'frontend/public/index';

        return $request->getView()->renderToResponse($response, $templateName, [
            'station' => $station,
            'nowplaying' => $np,
        ]);
    }
}
