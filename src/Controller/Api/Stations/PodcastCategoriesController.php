<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\PodcastCategory;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PodcastCategoriesController extends AbstractStationApiCrudController
{
    protected string $entityClass = PodcastCategory::class;
    protected string $resourceRouteName = 'api:stations:podcast-category';

    /**
     * @inheritDoc
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('pc')
            ->from(PodcastCategory::class, 'pc')
            ->orderBy('pc.title', 'ASC')
            ->addOrderBy('pc.sub_title', 'ASC');

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('pc.title LIKE :title')
                ->orWhere('pc.sub_title LIKE :subTitle')
                ->setParameter('title', '%' . $searchPhrase . '%')
                ->setParameter('subTitle', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }
}
