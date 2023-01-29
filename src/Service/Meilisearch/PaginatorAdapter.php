<?php

declare(strict_types=1);

namespace App\Service\Meilisearch;

use Closure;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Search\SearchResult;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Adapter which uses Meilisearch to perform a search, then uses a callback to hydrate with database records.
 *
 * @template TKey of array-key
 * @template T
 * @implements AdapterInterface<T>
 */
final readonly class PaginatorAdapter implements AdapterInterface
{
    public function __construct(
        private Indexes $indexClient,
        private Closure $hydrateCallback,
        private ?string $query,
        private array $searchParams = [],
        private array $options = [],
    ) {
    }

    public function getNbResults(): int
    {
        /** @var SearchResult $results */
        $results = $this->indexClient->search(
            $this->query,
            [
                ...$this->searchParams,
                'hitsPerPage' => 0,
            ],
            $this->options
        );

        return abs($results->getTotalHits() ?? 0);
    }

    public function getSlice(int $offset, int $length): iterable
    {
        /** @var SearchResult $results */
        $results = $this->indexClient->search(
            $this->query,
            [
                ...$this->searchParams,
                'offset' => $offset,
                'limit' => $length,
            ],
            $this->options
        );

        return ($this->hydrateCallback)($results->getHits());
    }
}
