<?php
namespace DF\Paginator;
class Doctrine extends \Zend_Paginator
{
    public function __construct($query, $page = 1, $limit = 10)
    {
        if ($query instanceof QueryBuilder)
            $query = $query->getQuery();

        $query->setMaxResults($limit);
        $query->setFirstResult(($page - 1) * $limit);

        parent::__construct(new Adapter\DoctrinePaginator($query));
        $this->setItemCountPerPage($limit);
        $this->setCurrentPageNumber($page);
    }
}