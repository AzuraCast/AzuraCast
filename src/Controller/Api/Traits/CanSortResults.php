<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Http\ServerRequest;
use App\Utilities\Types;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

trait CanSortResults
{
    protected function sortQueryBuilder(
        ServerRequest $request,
        QueryBuilder $queryBuilder,
        array $sortLookup,
        ?string $defaultSort,
        string $defaultSortOrder = Criteria::ASC
    ): QueryBuilder {
        [$sort, $sortOrder] = $this->getSortFromRequest($request, $defaultSortOrder);

        $sortValue = (null !== $sort && isset($sortLookup[$sort]))
            ? $sortLookup[$sort]
            : $defaultSort;

        if (null === $sortValue) {
            return $queryBuilder;
        }

        return $queryBuilder->addOrderBy($sortValue, $sortOrder);
    }

    protected function sortArray(
        ServerRequest $request,
        array $results,
        array $sortLookup,
        ?string $defaultSort = null,
        string $defaultSortOrder = Criteria::ASC
    ): array {
        [$sort, $sortOrder] = $this->getSortFromRequest($request, $defaultSortOrder);

        $sortValue = (null !== $sort && isset($sortLookup[$sort]))
            ? $sortLookup[$sort]
            : $defaultSort;

        if (null === $sortValue) {
            return $results;
        }

        $propertyAccessor = self::getPropertyAccessor();

        usort(
            $results,
            static fn(mixed $a, mixed $b) => self::sortByDotNotation($a, $b, $propertyAccessor, $sortValue, $sortOrder)
        );

        return $results;
    }

    /**
     * @return array{string, string}
     */
    protected function getSortFromRequest(
        ServerRequest $request,
        string $defaultSortOrder = Criteria::ASC
    ): array {
        $sortOrder = Types::stringOrNull($request->getParam('sortOrder'), true) ?? $defaultSortOrder;
        return [
            $request->getParam('sort'),
            (Criteria::DESC === strtoupper($sortOrder))
                ? Criteria::DESC
                : Criteria::ASC,
        ];
    }

    protected static function sortByDotNotation(
        object|array $a,
        object|array $b,
        PropertyAccessorInterface $propertyAccessor,
        string $sortValue,
        string $sortOrder
    ): int {
        $aVal = $propertyAccessor->getValue($a, $sortValue);
        $bVal = $propertyAccessor->getValue($b, $sortValue);

        if (is_string($aVal)) {
            $aVal = mb_strtolower($aVal, 'UTF-8');
        }
        if (is_string($bVal)) {
            $bVal = mb_strtolower($bVal, 'UTF-8');
        }

        return (Criteria::ASC === $sortOrder)
            ? $aVal <=> $bVal
            : $bVal <=> $aVal;
    }

    protected static ?PropertyAccessorInterface $propertyAccessor = null;

    protected static function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->disableExceptionOnInvalidIndex()
                ->disableExceptionOnInvalidPropertyPath()
                ->disableMagicMethods()
                ->getPropertyAccessor();
        }

        return self::$propertyAccessor;
    }
}
