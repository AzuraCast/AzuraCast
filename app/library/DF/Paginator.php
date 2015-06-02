<?php
namespace DF;

class Paginator extends \Zend\Paginator\Paginator
{
    public function __construct($paged_object, $page = 1, $limit = 10)
    {
        if ($paged_object instanceof \Doctrine\ORM\Query || $paged_object instanceof \Doctrine\ORM\QueryBuilder)
            parent::__construct(new Paginator\Adapter\DoctrineQuery($paged_object));
        elseif (is_array($paged_object))
            parent::__construct(new \Zend\Paginator\Adapter\ArrayAdapter($paged_object));
        else
            parent::__construct(new \Zend\Paginator\Adapter\NullFill($paged_object));

        $this->setItemCountPerPage($limit);
        $this->setCurrentPageNumber($page);
    }
}