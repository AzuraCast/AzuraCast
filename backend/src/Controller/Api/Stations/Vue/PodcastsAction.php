<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Controller\SingleActionInterface;
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
        $locale = $request->getCustomization()->getLocale();
        $userLocale = $locale->value;

        $languageOptions = Languages::getNames($userLocale);
        $categoriesOptions = PodcastCategory::getAvailableCategories();

        return $response->withJson([
            'languageOptions' => $languageOptions,
            'categoriesOptions' => $categoriesOptions,
        ]);
    }
}
