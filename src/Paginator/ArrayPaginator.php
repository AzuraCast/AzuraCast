<?php
namespace App\Paginator;

use App\Http\ServerRequest;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Traversable;

class ArrayPaginator extends AbstractPaginator
{
    protected ArrayCollection $collection;

    protected Query $query;

    protected Paginator $paginator;

    public function __construct($array, ?ServerRequest $request = null)
    {
        if ($array instanceof ArrayCollection) {
            $this->collection = $array;
        } elseif (is_array($array)) {
            $this->collection = new ArrayCollection($array);
        }

        parent::__construct($request);
    }

    public function getCollection(): ArrayCollection
    {
        return $this->collection;
    }

    public function getIterator(): Traversable
    {
        $criteria = new Criteria();

        if (!$this->isDisabled && $this->perPage !== -1) {
            $offset = ($this->currentPage - 1) * $this->perPage;

            $criteria->setFirstResult($offset);
            $criteria->setMaxResults($this->perPage);
        }

        return $this->collection->matching($criteria);
    }

    public function getCount(): int
    {
        return $this->collection->count();
    }
}