<?php
namespace DF\Paginator\Adapter;

use \Doctrine\ORM\Query;
use \Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrinePaginator implements \Zend_Paginator_Adapter_Interface
{
    /**
     * Paginator
     * @var Paginator
     */
    protected $paginator = null;

    /**
     * Item count
     * @var integer
     */
    protected $count = null;

    /**
     * Constructor.
     * @param Paginator $paginator
     */
    public function __construct(Query $query)
    {
        $this->paginator = new Paginator($query);
        $this->count = count($this->paginator);
    }

    /**
     * Returns an array of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        return $this->paginator->getIterator();
    }

    /**
     * Returns the total number of rows in the array.
     *
     * @return integer
     */
    public function count()
    {
        return $this->count;
    }
}