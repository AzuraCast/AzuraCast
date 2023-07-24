<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Controller\SingleActionInterface;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\PodcastCategory;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Languages;

final class PodcastsAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        $locale = $request->getCustomization()->getLocale();
        $userLocale = $locale->value;

        $languageOptions = Languages::getNames($userLocale);
        $categoriesOptions = PodcastCategory::getAvailableCategories();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Podcasts',
            id: 'station-podcasts',
            title: __('Podcasts'),
            props: [
                'listUrl' => $router->fromHere('api:stations:podcasts'),
                'newArtUrl' => $router->fromHere('api:stations:podcasts:new-art'),
                'stationUrl' => $router->fromHere('stations:index:index'),
                'quotaUrl' => $router->fromHere('api:stations:quota', [
                    'type' => StorageLocationTypes::StationPodcasts->value,
                ]),
                'locale' => substr($locale->value, 0, 2),
                'stationTimeZone' => $station->getTimezone(),
                'languageOptions' => $languageOptions,
                'categoriesOptions' => $categoriesOptions,
            ],
        );
    }
}
