<?php

declare(strict_types=1);

namespace App;

use App\Http\Response;
use App\Http\Router;
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

class Paginator implements IteratorAggregate, Countable
{
    protected RouterInterface $router;

    /** @var int The maximum number of records that can be viewed per page for unauthenticated users. */
    protected int $maxPerPage = 25;

    /** @var bool Whether the current request is from jQuery Bootgrid */
    protected bool $isBootgrid = false;

    /** @var bool Whether the user is currently authenticated on this request. */
    protected bool $isAuthenticated = false;

    /** @var bool Whether to show pagination controls. */
    protected bool $isDisabled = true;

    /** @var callable|null A callable postprocessor that can be run on each result. */
    protected $postprocessor;

    public function __construct(
        protected Pagerfanta $paginator,
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

        $this->paginator->setMaxPerPage(
            $this->isAuthenticated
                ? $perPage
                : min($perPage, $this->maxPerPage)
        );

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
            $this->paginator->setCurrentPage(1);
            $this->paginator->setMaxPerPage(PHP_INT_MAX);
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
        if ($this->router instanceof Router) {
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
        }

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

    public static function fromArray(array $input, ServerRequestInterface $request): self
    {
        return new self(
            new Pagerfanta(new ArrayAdapter($input)),
            $request
        );
    }

    public static function fromCollection(Collection $collection, ServerRequestInterface $request): self
    {
        return new self(
            new Pagerfanta(new CollectionAdapter($collection)),
            $request,
        );
    }

    public static function fromQueryBuilder(QueryBuilder $qb, ServerRequestInterface $request): self
    {
        return new self(
            new Pagerfanta(new QueryAdapter($qb)),
            $request
        );
    }

    public static function fromQuery(Query $query, ServerRequestInterface $request): self
    {
        return new self(
            new Pagerfanta(new QueryAdapter($query)),
            $request
        );
    }
}
