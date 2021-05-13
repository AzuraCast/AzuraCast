<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Categories;

use App\Entity\PodcastCategory;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ListAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $categoriesRaw = PodcastCategory::getAvailableCategories();

        return $response->withJson(
            array_map(
                static function ($categoryKey, $categoryName) {
                    if (str_contains($categoryKey, PodcastCategory::CATEGORY_SEPARATOR)) {
                        [$title, $subTitle] = explode(PodcastCategory::CATEGORY_SEPARATOR, $categoryKey);
                    } else {
                        $title = $categoryKey;
                        $subTitle = null;
                    }

                    return [
                        'id' => $categoryKey,
                        'display' => $categoryName,
                        'title' => $title,
                        'subTitle' => $subTitle,
                    ];
                },
                array_keys($categoriesRaw),
                $categoriesRaw
            )
        );
    }
}
