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

        $router = $request->getRouter();

        $pageClass = 'schedule station-' . $station->getShortName();
        if ($embed) {
            $pageClass .= ' embed';
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Schedule') . ' - ' . $station->getName(),
                'id' => 'station-schedule',
                'layout' => 'minimal',
                'layoutParams' => [
                    'page_class' => $pageClass,
                    'hide_footer' => true,
                ],
                'component' => 'Vue_PublicSchedule',
                'props' => [
                    'scheduleUrl' => (string)$router->named('api:stations:schedule', ['station_id' => $station->getId()]
                    ),
                    'stationName' => $station->getName(),
                    'stationTimeZone' => $station->getTimezone(),
                ],
            ]
        );
    }
}
