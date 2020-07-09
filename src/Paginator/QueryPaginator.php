<?php
namespace App\Paginator;

use App\Http\ServerRequest;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use Traversable;

class QueryPaginator extends AbstractPaginator
{
    protected Query $query;

    protected Paginator $paginator;

    /**
     * @param Query|QueryBuilder $query
     * @param ServerRequest|null $request
     */
    public function __construct($query, ?ServerRequest $request = null)
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        if (!($query instanceof Query)) {
            throw new InvalidArgumentException('Query specified is not a Doctrine query.');
        }

        $this->query = $query;

        parent::__construct($request);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function getIterator(): Traversable
    {
        return $this->getPaginator()->getIterator();
    }

    public function getCount(): int
    {
        return $this->getPaginator()->count();
    }

    public function getPaginator(): Paginator
    {
        if (!isset($this->paginator)) {
            if (!$this->isDisabled && $this->perPage !== -1) {
                $offset = ($this->currentPage - 1) * $this->perPage;
                $this->query->setFirstResult($offset);
                $this->query->setMaxResults($this->perPage);
            }

            $this->paginator = new Paginator($this->query);
        }

        return $this->paginator;
    }
}