<?php

declare(strict_types=1);

namespace App;

use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use Countable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Generator;
use IteratorAggregate;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\Collections\CollectionAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template TKey of array-key
 * @template T of mixed
 * @implements IteratorAggregate<TKey, T>
 */
final class Paginator implements IteratorAggregate, Countable
{
    private RouterInterface $router;

    /** @var int<1,max> The maximum number of records that can be viewed per page for unauthenticated users. */
    private int $maxPerPage = 25;

    /** @var bool Whether the user is currently authenticated on this request. */
    private bool $isAuthenticated;

    /** @var bool Whether to show pagination controls. */
    private bool $isDisabled = true;

    /** @var callable|null A callable postprocessor that can be run on each result. */
    private $postprocessor;

    /**
     * @param Pagerfanta<T> $paginator
     */
    public function __construct(
        private readonly Pagerfanta $paginator,
        ServerRequestInterface $request
    ) {
        $this->router = $request->getAttribute(ServerRequest::ATTR_ROUTER);

        $user = $request->getAttribute(ServerRequest::ATTR_USER);
        $this->isAuthenticated = ($user !== null);

        $params = $request->getQueryParams();

        $perPage = $params['rowCount'] ?? $params['per_page'] ?? null;
        $currentPage = $params['current'] ?? $params['page'] ?? null;
        if (null !== $perPage) {
            $this->setPerPage((int)$perPage);
        }
        if (null !== $currentPage) {
            $this->setCurrentPage((int)$currentPage);
        }
    }

    public function getCurrentPage(): int
    {
        return $this->paginator->getCurrentPage();
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->paginator->setCurrentPage(
            ($currentPage >= 1) ? $currentPage : 1
        );
    }

    public function setMaxPerPage(int $maxPerPage): void
    {
        $this->maxPerPage = ($maxPerPage > 0) ? $maxPerPage : 1;
        $this->isDisabled = false;
    }

    public function getPerPage(): int
    {
        return $this->paginator->getMaxPerPage();
    }

    public function setPerPage(int $perPage): void
    {
        if ($perPage <= 0) {
            $perPage = PHP_INT_MAX;
        }

        /** @var int<1,max> $maxPerPage */
        $maxPerPage = $this->isAuthenticated
            ? $perPage
            : min($perPage, $this->maxPerPage);

        $this->paginator->setMaxPerPage($maxPerPage);

        $this->isDisabled = false;
    }

    public function setPostprocessor(callable $postprocessor): void
    {
        $this->postprocessor = $postprocessor;
    }

    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function setIsDisabled(bool $isDisabled): void
    {
        $this->isDisabled = $isDisabled;
    }

    public function getIterator(): Generator
    {
        $iterator = $this->paginator->getIterator();
        if ($this->postprocessor) {
            foreach ($iterator as $row) {
                yield ($this->postprocessor)($row, $this);
            }
        } else {
            yield from $iterator;
        }
    }

    public function count(): int
    {
        return $this->paginator->getNbResults();
    }

    public function write(Response $response): ResponseInterface
    {
        if ($this->isDisabled) {
            /** @var int<1,max> $maxPerPage */
            $maxPerPage = PHP_INT_MAX;

            $this->paginator->setCurrentPage(1);
            $this->paginator->setMaxPerPage($maxPerPage);
        }

        $total = $this->count();

        $totalPages = $this->paginator->getNbPages();

        $results = iterator_to_array($this->getIterator(), false);

        if ($this->isDisabled) {
            return $response->withJson($results);
        }

        $pageLinks = [];
        $pageLinks['first'] = $this->router->fromHereWithQuery(null, [], ['page' => 1]);

        $prevPage = $this->paginator->hasPreviousPage()
            ? $this->paginator->getPreviousPage()
            : 1;

        $pageLinks['previous'] = $this->router->fromHereWithQuery(null, [], ['page' => $prevPage]);

        $nextPage = $this->paginator->hasNextPage()
            ? $this->paginator->getNextPage()
            : $this->paginator->getNbPages();

        $pageLinks['next'] = $this->router->fromHereWithQuery(null, [], ['page' => $nextPage]);

        $pageLinks['last'] = $this->router->fromHereWithQuery(null, [], ['page' => $totalPages]);

        return $response->withJson(
            [
                'page' => $this->getCurrentPage(),
                'per_page' => $this->getPerPage(),
                'total' => $total,
                'total_pages' => $totalPages,
                'links' => $pageLinks,
                'rows' => $results,
            ]
        );
    }

    /**
     * @template X of mixed
     *
     * @param AdapterInterface<X> $adapter
     * @return static<array-key, X>
     */
    public static function fromAdapter(
        AdapterInterface $adapter,
        ServerRequestInterface $request
    ): self {
        return new self(
            new Pagerfanta($adapter),
            $request
        );
    }

    /**
     * @template XKey of array-key
     * @template X of mixed
     *
     * @param array<XKey, X> $input
     * @return static<XKey, X>
     */
    public static function fromArray(array $input, ServerRequestInterface $request): self
    {
        return self::fromAdapter(new ArrayAdapter($input), $request);
    }

    /**
     * @template XKey of array-key
     * @template X of mixed
     *
     * @param Collection<XKey, X> $collection
     * @return static<XKey, X>
     */
    public static function fromCollection(Collection $collection, ServerRequestInterface $request): self
    {
        return self::fromAdapter(new CollectionAdapter($collection), $request);
    }

    /**
     * @return static<int, mixed>
     */
    public static function fromQueryBuilder(QueryBuilder $qb, ServerRequestInterface $request): self
    {
        return self::fromAdapter(new QueryAdapter($qb), $request);
    }

    /**
     * @return static<int, mixed>
     */
    public static function fromQuery(Query $query, ServerRequestInterface $request): self
    {
        return self::fromAdapter(new QueryAdapter($query), $request);
    }
}
