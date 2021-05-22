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
        $station = $request->getStation();

        $userLocale = (string)$request->getCustomization()->getLocale();

        $languageOptions = Languages::getNames($userLocale);
        $categoriesOptions = PodcastCategory::getAvailableCategories();

        return $request->getView()->renderToResponse(
            $response,
            'stations/podcasts/index',
            [
                'stationId' => $station->getId(),
                'stationTz' => $station->getTimezone(),
                'languageOptions' => $languageOptions,
                'categoriesOptions' => $categoriesOptions,
            ]
        );
    }
}
