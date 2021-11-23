<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Languages;

class PodcastsAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();
        $customization = $request->getCustomization();
        $station = $request->getStation();

        $userLocale = (string)$request->getCustomization()->getLocale();

        $languageOptions = Languages::getNames($userLocale);
        $categoriesOptions = Entity\PodcastCategory::getAvailableCategories();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsPodcasts',
            id: 'station-podcasts',
            title: __('Podcasts'),
            props: [
                'listUrl'           => (string)$router->fromHere('api:stations:podcasts'),
                'newArtUrl'         => (string)$router->fromHere('api:stations:podcasts:new-art'),
                'stationUrl'        => (string)$router->fromHere('stations:index:index'),
                'quotaUrl'          => (string)$router->fromHere('api:stations:quota', [
                    'type' => Entity\StorageLocation::TYPE_STATION_PODCASTS,
                ]),
                'locale'            => substr((string)$customization->getLocale(), 0, 2),
                'stationTimeZone'   => $station->getTimezone(),
                'languageOptions'   => $languageOptions,
                'categoriesOptions' => $categoriesOptions,
            ],
        );
    }
}
