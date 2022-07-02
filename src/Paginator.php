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
use IteratorAggregate;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\Collections\CollectionAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

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

    /** @var bool Whether the current request is from jQuery Bootgrid */
    private bool $isBootgrid = false;

    /** @var bool Whether the user is currently authenticated on this request. */
    private bool $isAuthenticated = false;

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
        $this->isBootgrid = isset($params['rowCount']) || isset($params['searchPhrase']);

        if ($this->isBootgrid) {
            if (isset($params['rowCount'])) {
                $this->setPerPage((int)$params['rowCount']);
            }
            if (isset($params['current'])) {
                $this->setCurrentPage((int)$params['current']);
            }
        } else {
            if (isset($params['per_page'])) {
                $this->setPerPage((int)$params['per_page']);
            }
            if (isset($params['page'])) {
                $this->setCurrentPage((int)$params['page']);
            }
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

    public function isFromBootgrid(): bool
    {
        return $this->isBootgrid;
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

    public function getIterator(): Traversable
    {
        return $this->paginator->getIterator();
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

        $iterator = $this->getIterator();
        $total = $this->count();

        $totalPages = $this->paginator->getNbPages();

        if ($this->postprocessor) {
            $results = [];
            $postprocessor = $this->postprocessor;
            foreach ($iterator as $result) {
                $results[] = $postprocessor($result, $this);
            }
        } else {
            $results = iterator_to_array($iterator, false);
        }

        if ($this->isDisabled) {
            return $response->withJson($results);
        }

        if ($this->isBootgrid) {
            return $response->withJson(
                [
                    'current' => $this->getCurrentPage(),
                    'rowCount' => $this->getPerPage(),
                    'total' => $total,
                    'rows' => $results,
                ]
            );
        }

        $pageLinks = [];
        $pageLinks['first'] = (string)$this->router->fromHereWithQuery(null, [], ['page' => 1]);

        $prevPage = $this->paginator->hasPreviousPage()
            ? $this->paginator->getPreviousPage()
            : 1;

        $pageLinks['previous'] = (string)$this->router->fromHereWithQuery(null, [], ['page' => $prevPage]);

        $nextPage = $this->paginator->hasNextPage()
            ? $this->paginator->getNextPage()
            : $this->paginator->getNbPages();

        $pageLinks['next'] = (string)$this->router->fromHereWithQuery(null, [], ['page' => $nextPage]);

        $pageLinks['last'] = (string)$this->router->fromHereWithQuery(null, [], ['page' => $totalPages]);

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
     * @template XKey of array-key
     * @template X of mixed
     *
     * @param array<XKey, X> $input
     * @return static<XKey, X>
     */
    public static function fromArray(array $input, ServerRequestInterface $request): self
    {
        return new self(
            new Pagerfanta(new ArrayAdapter($input)),
            $request
        );
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
        return new self(
            new Pagerfanta(new CollectionAdapter($collection)),
            $request,
        );
    }

    /**
     * @return static<int, mixed>
     */
    public static function fromQueryBuilder(QueryBuilder $qb, ServerRequestInterface $request): self
    {
        return new self(
            new Pagerfanta(new QueryAdapter($qb)),
            $request
        );
    }

    /**
     * @return static<int, mixed>
     */
    public static function fromQuery(Query $query, ServerRequestInterface $request): self
    {
        return new self(
            new Pagerfanta(new QueryAdapter($query)),
            $request
        );
    }
}
