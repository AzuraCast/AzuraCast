<?php

declare(strict_types=1);

namespace App\Service\Meilisearch;

use Meilisearch\Endpoints\Indexes;
use Meilisearch\Search\SearchResult;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Adapter which uses Meilisearch to perform a search.
 *
 * @template T of array
 * @implements AdapterInterface<T>
 */
final class PaginatorAdapter implements AdapterInterface
{
    public function __construct(
        private readonly Indexes $indexClient,
        private readonly ?string $query,
        private readonly array $searchParams = [],
        private readonly array $options = [],
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

        yield from $results->getHits();
    }
}
