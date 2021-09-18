<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\PodcastCategory;
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
        $categoriesOptions = PodcastCategory::getAvailableCategories();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Podcasts'),
                'id' => 'station-podcasts',
                'component' => 'Vue_StationsPodcasts',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:stations:podcasts'),
                    'newArtUrl' => (string)$router->fromHere('api:stations:podcasts:new-art'),
                    'stationUrl' => (string)$router->fromHere('stations:index:index'),
                    'locale' => substr((string)$customization->getLocale(), 0, 2),
                    'stationTimeZone' => $station->getTimezone(),
                    'languageOptions' => $languageOptions,
                    'categoriesOptions' => $categoriesOptions,
                ],
            ]
        );
    }
}
