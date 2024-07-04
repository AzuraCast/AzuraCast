<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Http\ServerRequest;
use App\Utilities\Types;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

trait CanSortResults
{
    use UsesPropertyAccessor;

    protected function sortQueryBuilder(
        ServerRequest $request,
        QueryBuilder $queryBuilder,
        array $sortLookup,
        ?string $defaultSort,
        Order $defaultSortOrder = Order::Ascending
    ): QueryBuilder {
        [$sort, $sortOrder] = $this->getSortFromRequest($request, $defaultSortOrder);

        $sortValue = (null !== $sort && isset($sortLookup[$sort]))
            ? $sortLookup[$sort]
            : $defaultSort;

        if (null === $sortValue) {
            return $queryBuilder;
        }

        return $queryBuilder->addOrderBy($sortValue, $sortOrder->value);
    }

    protected function sortArray(
        ServerRequest $request,
        array $results,
        array $sortLookup,
        ?string $defaultSort = null,
        Order $defaultSortOrder = Order::Ascending
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
     * @return array{string|null, Order}
     */
    protected function getSortFromRequest(
        ServerRequest $request,
        Order $defaultSortOrder = Order::Ascending
    ): array {
        $sortValue = Types::stringOrNull($request->getParam('sort'), true);
        $sortOrder = Types::stringOrNull($request->getParam('sortOrder'), true);

        return [
            $sortValue,
            (null !== $sortValue && null !== $sortOrder)
                ? Order::tryFrom(strtoupper($sortOrder)) ?? $defaultSortOrder
                : $defaultSortOrder,
        ];
    }

    protected static function sortByDotNotation(
        object|array $a,
        object|array $b,
        PropertyAccessorInterface $propertyAccessor,
        string $sortValue,
        Order $sortOrder = Order::Ascending
    ): int {
        try {
            $aVal = $propertyAccessor->getValue($a, $sortValue);
        } catch (UnexpectedTypeException) {
            $aVal = null;
        }

        try {
            $bVal = $propertyAccessor->getValue($b, $sortValue);
        } catch (UnexpectedTypeException) {
            $bVal = null;
        }

        if (is_string($aVal)) {
            $aVal = mb_strtolower($aVal, 'UTF-8');
        }
        if (is_string($bVal)) {
            $bVal = mb_strtolower($bVal, 'UTF-8');
        }

        return (Order::Ascending === $sortOrder)
            ? $aVal <=> $bVal
            : $bVal <=> $aVal;
    }
}
