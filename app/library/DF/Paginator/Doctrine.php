<?php
namespace DF\Paginator;
class Doctrine extends \Zend_Paginator
{
    public function __construct($query, $page = 1, $limit = 10)
    {
        parent::__construct(new Adapter\DoctrineQuery($query));

        $this->setItemCountPerPage($limit);
        $this->setCurrentPageNumber($page);
    }
}