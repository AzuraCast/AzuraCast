<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Http\ServerRequest;
use App\Utilities\Types;
use Doctrine\ORM\QueryBuilder;

trait CanSearchResults
{
    use UsesPropertyAccessor;

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

    /**
     * @param string[] $fieldsToSearch
     */
    protected function searchArray(
        ServerRequest $request,
        array $results,
        array $fieldsToSearch,
        string $searchParam = 'searchPhrase'
    ): array {
        $searchPhrase = $this->getSearchPhrase($request, $searchParam);
        if (null === $searchPhrase) {
            return $results;
        }

        $propertyAccessor = self::getPropertyAccessor();

        return array_filter(
            $results,
            function (mixed $result) use ($propertyAccessor, $searchPhrase, $fieldsToSearch): bool {
                foreach ($fieldsToSearch as $field) {
                    $fieldVal = Types::string(
                        $propertyAccessor->getValue($result, $field)
                    );

                    if (false !== mb_stripos($fieldVal, $searchPhrase)) {
                        return true;
                    }
                }

                return false;
            }
        );
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
