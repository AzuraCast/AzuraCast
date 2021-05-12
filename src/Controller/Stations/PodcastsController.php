<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\PodcastCategory;
use App\Entity\Repository\PodcastCategoryRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Languages;

class PodcastsController
{
    protected PodcastCategoryRepository $podcastCategoryRepository;

    public function __construct(PodcastCategoryRepository $podcastCategoryRepository)
    {
        $this->podcastCategoryRepository = $podcastCategoryRepository;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $userLocale = $request->getUser()->getLocale() ?? 'en';

        $languageOptions = Languages::getNames($userLocale);
        $categoriesOptions = $this->fetchCategoriesOptions();

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

    /**
     * @return mixed[]
     */
    protected function fetchCategoriesOptions(): array
    {
        $categoryOptions = [];

        $categories = $this->podcastCategoryRepository->fetchCategories();

        /** @var PodcastCategory $category */
        foreach ($categories as $category) {
            if ($category->getSubTitle() !== null) {
                $categoryOptions[$category->getId()] = sprintf(
                    '%s - %s',
                    $category->getTitle(),
                    $category->getSubTitle()
                );

                continue;
            }

            $categoryOptions[$category->getId()] = $category->getTitle();
        }

        return $categoryOptions;
    }
}
