<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Psr\Http\Message\ResponseInterface;

final class SoundExchangeAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $tzObject = $request->getStation()->getTimezoneObject();

        $defaultStartDate = CarbonImmutable::parse('first day of last month', $tzObject)->format('Y-m-d');
        $defaultEndDate = CarbonImmutable::parse('last day of last month', $tzObject)->format('Y-m-d');

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsReportsSoundExchange',
            id: 'station-report-soundexchange',
            title: __('SoundExchange Report'),
            props: [
                'apiUrl' => $router->fromHere('api:stations:reports:soundexchange'),
                'startDate' => $defaultStartDate,
                'endDate' => $defaultEndDate,
            ]
        );
    }
}
