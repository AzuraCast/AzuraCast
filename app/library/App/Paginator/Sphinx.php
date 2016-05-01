<?php
namespace App\Paginator;

class Sphinx implements \Countable, \IteratorAggregate
{
    protected $_page_number;
    protected $_num_per_page;
    protected $_num_results;
    protected $_current_results;

    public function __construct($current_results, $total_results = 0, $page = 1, $limit = 10)
    {
        $this->_current_results = new \ArrayObject((array)$current_results);

        if ($total_results === 0)
            $this->_num_results = count($this->_current_results);
        else
            $this->_num_results = (int)$total_results;

        $this->_page_number = (int)$page;
        $this->_num_per_page = (int)$limit;
    }

    public function count()
    {
        return $this->_num_results;
    }

    public function getIterator()
    {
        return $this->_current_results->getIterator();
    }

    public function getPageCount()
    {
        return ceil($this->_num_results / $this->_num_per_page);
    }

    public function getPages()
    {
        $pageCount         = $this->getPageCount();
        $currentPageNumber = $this->_page_number;

        $pages = new \stdClass();
        $pages->pageCount        = $pageCount;
        $pages->itemCountPerPage = $this->_num_per_page;
        $pages->first            = 1;
        $pages->current          = $currentPageNumber;
        $pages->last             = $pageCount;

        // Previous and next
        if ($currentPageNumber - 1 > 0) {
            $pages->previous = $currentPageNumber - 1;
        }

        if ($currentPageNumber + 1 <= $pageCount) {
            $pages->next = $currentPageNumber + 1;
        }

        // Pages in range
        $pages_in_range = array();
        for($i = 1; $i <= $pageCount; $i++)
            $pages_in_range[] = $i;

        $pages->pagesInRange     = $pages_in_range;
        $pages->firstPageInRange = 1;
        $pages->lastPageInRange  = $pageCount;

        return $pages;
    }
}