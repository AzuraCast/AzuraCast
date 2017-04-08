<?php
namespace App\Paginator;

class Doctrine implements \Countable, \IteratorAggregate
{
    protected $_page_number;

    protected $_num_per_page;

    protected $_query;

    protected $_paginator;

    public function __construct($query, $page = 1, $limit = 10)
    {
        $this->_page_number = $page;
        $this->_num_per_page = $limit;

        if ($query instanceof \Doctrine\ORM\QueryBuilder) {
            $query = $query->getQuery();
        }

        $query->setFirstResult(($page - 1) * $limit);
        $query->setMaxResults($limit);

        $this->_query = $query;
        $this->_paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($this->_query);
    }

    public function count()
    {
        return $this->_paginator->count();
    }

    public function getIterator()
    {
        return $this->_paginator->getIterator();
    }

    public function getPageCount()
    {
        return ceil($this->_paginator->count() / $this->_num_per_page);
    }

    public function getPages()
    {
        $pageCount = $this->getPageCount();
        $currentPageNumber = $this->_page_number;

        $pages = new \stdClass();
        $pages->pageCount = $pageCount;
        $pages->itemCountPerPage = $this->_num_per_page;
        $pages->first = 1;
        $pages->current = $currentPageNumber;
        $pages->last = $pageCount;

        // Previous and next
        if ($currentPageNumber - 1 > 0) {
            $pages->previous = $currentPageNumber - 1;
        }

        if ($currentPageNumber + 1 <= $pageCount) {
            $pages->next = $currentPageNumber + 1;
        }

        // Pages in range
        $pages_in_range = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $pages_in_range[] = $i;
        }

        $pages->pagesInRange = $pages_in_range;
        $pages->firstPageInRange = 1;
        $pages->lastPageInRange = $pageCount;

        return $pages;
    }
}