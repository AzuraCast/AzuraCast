<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\PodcastCategory;
use App\Doctrine\Repository;
use App\Entity\Station;

class PodcastCategoryRepository extends Repository
{
    /**
     * @return PodcastCategory[]
     */
    public function fetchCategories(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @param int[] $categoryIds
     *
     * @return PodcastCategory[]
     */
    public function fetchCategoriesByIds(array $categoryIds): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        return $queryBuilder
            ->select('pc')
            ->from(PodcastCategory::class, 'pc')
            ->where($queryBuilder->expr()->in('pc.id', $categoryIds))
            ->getQuery()
            ->getResult();
    }
}
