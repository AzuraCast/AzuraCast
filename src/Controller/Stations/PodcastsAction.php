<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Languages;

final class PodcastsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        $locale = $request->getCustomization()->getLocale();
        $userLocale = $locale->value;

        $languageOptions = Languages::getNames($userLocale);
        $categoriesOptions = Entity\PodcastCategory::getAvailableCategories();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsPodcasts',
            id: 'station-podcasts',
            title: __('Podcasts'),
            props: [
                'listUrl' => $router->fromHere('api:stations:podcasts'),
                'newArtUrl' => $router->fromHere('api:stations:podcasts:new-art'),
                'stationUrl' => $router->fromHere('stations:index:index'),
                'quotaUrl' => $router->fromHere('api:stations:quota', [
                    'type' => Entity\Enums\StorageLocationTypes::StationPodcasts->value,
                ]),
                'locale' => substr($locale->value, 0, 2),
                'stationTimeZone' => $station->getTimezone(),
                'languageOptions' => $languageOptions,
                'categoriesOptions' => $categoriesOptions,
            ],
        );
    }
}
