<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Enums\ListFilterMode;
use App\Exception\ValidationException;
use App\Http\ServerRequest;
use App\Utilities\ListFilter;
use App\Utilities\Types;
use Doctrine\ORM\QueryBuilder;

trait CanFilterResults
{
    /**
     * Apply conditions from a filter[key]=value query parameter.
     * - Keys not in the lookup are ignored
     * - Values outside an enum filter's allowed values throw a 400 error
     *
     * @param array<string, ListFilter> $filterLookup
     */
    protected function filterQueryBuilder(
        ServerRequest $request,
        QueryBuilder $queryBuilder,
        array $filterLookup,
        string $filterParam = 'filter'
    ): QueryBuilder {
        $filters = Types::array($request->getParam($filterParam));

        foreach ($filterLookup as $key => $filter) {
            $values = self::getFilterValues($key, $filters[$key] ?? null, $filter);
            if ($values === []) {
                continue;
            }

            $queryBuilder = match ($filter->mode) {
                ListFilterMode::Equals => self::applyEqualsFilter($queryBuilder, $key, $filter, $values),
                ListFilterMode::Contains => self::applyContainsFilter($queryBuilder, $key, $filter, $values),
            };
        }

        return $queryBuilder;
    }

    /**
     * @param list<string> $values
     */
    private static function applyEqualsFilter(
        QueryBuilder $queryBuilder,
        string $key,
        ListFilter $filter,
        array $values
    ): QueryBuilder {
        $parameter = "filter_{$key}";
        $isSingleValue = count($values) === 1;

        return $queryBuilder->andWhere(
            $isSingleValue
                ? "{$filter->field} = :{$parameter}"
                : "{$filter->field} IN (:{$parameter})"
        )->setParameter(
            $parameter,
            $isSingleValue ? $values[0] : $values
        );
    }

    /**
     * @param list<string> $values
     */
    private static function applyContainsFilter(
        QueryBuilder $queryBuilder,
        string $key,
        ListFilter $filter,
        array $values
    ): QueryBuilder {
        $conditions = [];

        foreach ($values as $index => $value) {
            $parameter = "filter_{$key}_{$index}";
            $conditions[] = "{$filter->field} LIKE :{$parameter}";
            $queryBuilder->setParameter($parameter, "%{$value}%");
        }

        $condition = implode(' OR ', $conditions);

        return $queryBuilder->andWhere("({$condition})");
    }

    /**
     * @return list<string>
     */
    private static function getFilterValues(
        string $key,
        mixed $rawValue,
        ListFilter $filter
    ): array {
        $rawValues = is_array($rawValue) ? $rawValue : [$rawValue];

        $values = [];
        foreach ($rawValues as $rawItem) {
            if ($rawItem === null) {
                continue;
            }

            if (!is_scalar($rawItem)) {
                throw self::invalidFilterValue(
                    $key,
                    get_debug_type($rawItem),
                    $filter
                );
            }

            $value = trim((string) $rawItem);
            if ($value === '') {
                continue;
            }

            $allowedValues = $filter->allowedValues;
            if ($allowedValues !== null && !in_array($value, $allowedValues, true)) {
                throw self::invalidFilterValue($key, $value, $filter);
            }

            $values[] = $value;
        }

        return $values;
    }

    private static function invalidFilterValue(
        string $key,
        string $value,
        ListFilter $filter
    ): ValidationException {
        $allowedValues = $filter->allowedValues;

        if ($allowedValues === null) {
            return new ValidationException(
                sprintf(__('Invalid value "%s" for filter "%s".'), $value, $key)
            );
        }

        return new ValidationException(
            sprintf(
                __('Invalid value "%s" for filter "%s". Allowed values: %s'),
                $value,
                $key,
                implode(', ', $allowedValues)
            )
        );
    }
}
