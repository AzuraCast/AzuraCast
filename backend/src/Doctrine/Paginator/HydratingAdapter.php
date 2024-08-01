<?php

declare(strict_types=1);

namespace App\Doctrine\Paginator;

use Closure;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Adapter which hydrates paginated records with a callback query.
 *
 * @template T
 * @implements AdapterInterface<T>
 */
final class HydratingAdapter implements AdapterInterface
{
    /**
     * @param AdapterInterface<T> $wrapped
     */
    public function __construct(
        private readonly AdapterInterface $wrapped,
        private readonly Closure $hydrateCallback,
    ) {
    }

    public function getNbResults(): int
    {
        return $this->wrapped->getNbResults();
    }

    public function getSlice(int $offset, int $length): iterable
    {
        $results = $this->wrapped->getSlice($offset, $length);
        yield from ($this->hydrateCallback)($results);
    }
}
