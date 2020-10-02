<?php
namespace App\Paginator;

use App\Http\Response;
use App\Http\Router;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Traversable;

abstract class AbstractPaginator
{
    protected RouterInterface $router;

    protected int $currentPage = 1;

    protected int $perPage = 15;

    protected int $maxPerPage = 50;

    /** @var bool Whether the current request is from jQuery Bootgrid */
    protected bool $isBootgrid = false;

    /** @var bool Whether to show pagination controls. */
    protected bool $isDisabled = false;

    /** @var callable|null A callable postprocessor that can be run on each result. */
    protected $postprocessor;

    public function __construct(?ServerRequest $request = null)
    {
        if (null !== $request) {
            $this->setFromRequest($request);
        }
    }

    public function setFromRequest(ServerRequest $request): void
    {
        $params = $request->getQueryParams();

        $this->isDisabled = true;
        $this->isBootgrid = isset($params['rowCount']) || isset($params['searchPhrase']);

        if ($this->isBootgrid) {
            $this->setCurrentPage((int)$params['current']);
            $this->setPerPage((int)$params['rowCount']);
        } else {
            if (isset($params['page'])) {
                $this->setCurrentPage((int)$params['page']);
            }
            if (isset($params['per_page'])) {
                $this->setPerPage((int)$params['per_page']);
            }
        }

        $this->router = $request->getRouter();
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = ($currentPage > 0) ? $currentPage : 1;
    }

    public function setMaxPerPage(int $maxPerPage): void
    {
        $this->maxPerPage = ($maxPerPage > 0) ? $maxPerPage : 1;
        $this->isDisabled = false;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $perPage): void
    {
        if ($perPage > 0) {
            $this->perPage = ($perPage <= $this->maxPerPage) ? $perPage : $this->maxPerPage;
        } else {
            $this->perPage = -1;
        }

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

    public function write(Response $response): ResponseInterface
    {
        $iterator = $this->getIterator();
        $total = $this->getCount();

        $total_pages = ($this->perPage === -1)
            ? 1
            : ceil($total / $this->perPage);

        if ($this->postprocessor) {
            $results = [];
            $postprocessor = $this->postprocessor;
            foreach ($iterator as $result) {
                $results[] = $postprocessor($result);
            }
        } else {
            $results = iterator_to_array($iterator);
        }

        if ($this->isDisabled) {
            return $response->withJson($results);
        }

        if ($this->isBootgrid) {
            return $response->withJson([
                'current' => $this->currentPage,
                'rowCount' => $this->perPage,
                'total' => $total,
                'rows' => $results,
            ]);
        }

        $page_links = [];
        if ($this->router instanceof Router) {
            $page_links['first'] = $this->router->fromHereWithQuery(null, [], ['page' => 1]);

            $prev_page = ($this->currentPage > 1) ? $this->currentPage - 1 : 1;
            $page_links['previous'] = $this->router->fromHereWithQuery(null, [], ['page' => $prev_page]);

            $next_page = ($this->currentPage < $total_pages) ? $this->currentPage + 1 : $total_pages;
            $page_links['next'] = $this->router->fromHereWithQuery(null, [], ['page' => $next_page]);

            $page_links['last'] = $this->router->fromHereWithQuery(null, [], ['page' => $total_pages]);
        }

        return $response->withJson([
            'page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total' => $total,
            'total_pages' => $total_pages,
            'links' => $page_links,
            'rows' => $results,
        ]);
    }

    abstract public function getIterator(): Traversable;

    abstract public function getCount(): int;
}
