<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ScheduleAction
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

        return $request->getView()->renderToResponse(
            $response,
            'frontend/public/schedule',
            [
                'embed' => $embed,
                'station' => $station,
                'station_tz' => $station->getTimezone(),
            ]
        );
    }
}
