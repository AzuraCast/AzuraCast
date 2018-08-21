<?php
namespace App\Doctrine;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

class Paginator
{
    /** @var Query */
    protected $query;

    /** @var Router */
    protected $router;

    /** @var int */
    protected $current_page = 1;

    /** @var int */
    protected $per_page = 15;

    /** @var int */
    protected $max_per_page = 50;

    /** @var bool Whether the current request is from jQuery Bootgrid */
    protected $is_bootgrid;

    /** @var callable|null A callable postprocessor that can be run on each result. */
    protected $postprocessor;

    public function __construct($query)
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        if (!($query instanceof Query)) {
            throw new \InvalidArgumentException('Query specified is not a Doctrine query.');
        }

        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function setCurrentPage(int $current_page): void
    {
        $this->current_page = ($current_page > 0) ? $current_page : 1;
    }

    public function getCurrentPage(): int
    {
        return $this->current_page;
    }

    public function setMaxPerPage(int $max_per_page): void
    {
        $this->max_per_page = ($max_per_page > 0) ? $max_per_page : 1;
    }

    public function setPerPage(int $per_page): void
    {
        if ($per_page > 0) {
            $this->per_page = ($per_page <= $this->max_per_page) ? $per_page : $this->max_per_page;
        } else {
            $this->per_page = 1;
        }
    }

    public function getPerPage(): int
    {
        return $this->per_page;
    }

    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function isFromBootgrid(): bool
    {
        return $this->is_bootgrid;
    }

    public function setFromRequest(Request $request)
    {
        $this->is_bootgrid = $request->hasParam('rowCount') || $request->hasParam('searchPhrase');

        if ($this->is_bootgrid) {
            $this->setCurrentPage((int)$request->getParam('current'));
            $this->setPerPage((int)$request->getParam('rowCount'));
        } else {
            if ($request->hasParam('page')) {
                $this->setCurrentPage($request->getParam('page'));
            }
            if ($request->hasParam('per_page')) {
                $this->setPerPage($request->getParam('per_page'));
            }
        }

        $this->setRouter($request->getRouter());
    }

    public function setPostprocessor(callable $postprocessor)
    {
        $this->postprocessor = $postprocessor;
    }

    public function getPaginator()
    {
        static $paginator;

        if (!$paginator) {
            $offset = ($this->current_page - 1) * $this->per_page;

            $this->query->setFirstResult($offset);
            $this->query->setMaxResults($this->per_page);

            $paginator = new DoctrinePaginator($this->query);
        }

        return $paginator;
    }

    public function write(Response $response): Response
    {
        $paginator = $this->getPaginator();

        $total = count($paginator);
        $total_pages = ceil($total / $this->per_page);

        $results = iterator_to_array($paginator);
        if ($this->postprocessor) {
            $results = array_map($this->postprocessor, $results);
        }

        if ($this->is_bootgrid) {
            return $response->withJson([
                'current'   => $this->current_page,
                'rowCount'  => $this->per_page,
                'total'     => count($paginator),
                'rows'      => $results,
            ]);
        }

        $page_links = [];
        if ($this->router instanceof Router) {
            $page_links['first'] = $this->router->fromHereWithQuery(null, [], ['page' => 1]);

            $prev_page = ($this->current_page > 1) ? $this->current_page - 1 : 1;
            $page_links['previous'] = $this->router->fromHereWithQuery(null, [], ['page' => $prev_page]);

            $next_page = ($this->current_page < $total_pages) ? $this->current_page + 1 : $total_pages;
            $page_links['next'] = $this->router->fromHereWithQuery(null, [], ['page' => $next_page]);

            $page_links['last'] = $this->router->fromHereWithQuery(null, [], ['page' => $total_pages]);
        }

        return $response->withJson([
            'page'      => $this->current_page,
            'per_page'  => $this->per_page,
            'total'     => count($paginator),
            'total_pages' => $total_pages,
            'links'     => $page_links,
            'rows'      => $results,
        ]);
    }
}
