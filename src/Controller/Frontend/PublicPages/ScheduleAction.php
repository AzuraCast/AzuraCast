<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ScheduleAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        ?string $embed = null
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $router = $request->getRouter();

        $pageClass = 'schedule station-' . $station->getShortName();
        if (null !== $embed) {
            $pageClass .= ' embed';
        }

        return $request->getView()->renderVuePage(
            response: $response
                ->withHeader('X-Frame-Options', '*'),
            component: 'Vue_PublicSchedule',
            id: 'station-schedule',
            layout: 'minimal',
            title: __('Schedule') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => $pageClass,
                'hide_footer' => true,
            ],
            props: [
                'scheduleUrl' => $router->named('api:stations:schedule', [
                    'station_id' => $station->getId(),
                ]),
                'stationName' => $station->getName(),
                'stationTimeZone' => $station->getTimezone(),
            ],
        );
    }
}
