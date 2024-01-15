<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Http\ServerRequest;
use App\Utilities\Types;
use Doctrine\ORM\QueryBuilder;

trait CanSearchResults
{
    /**
     * @param string[] $fieldsToSearch
     */
    protected function searchQueryBuilder(
        ServerRequest $request,
        QueryBuilder $queryBuilder,
        array $fieldsToSearch,
        string $searchParam = 'searchPhrase'
    ): QueryBuilder {
        $searchPhrase = $this->getSearchPhrase($request, $searchParam);
        if (null === $searchPhrase) {
            return $queryBuilder;
        }

        $searchQuery = [];
        foreach ($fieldsToSearch as $field) {
            $searchQuery[] = $field . ' LIKE :search';
        }

        return $queryBuilder->andWhere(
            implode(' OR ', $searchQuery)
        )->setParameter('search', '%' . $searchPhrase . '%');
    }

    protected function getSearchPhrase(
        ServerRequest $request,
        string $searchParam = 'searchPhrase'
    ): ?string {
        return Types::stringOrNull(
            $request->getParam($searchParam),
            true
        );
    }
}
