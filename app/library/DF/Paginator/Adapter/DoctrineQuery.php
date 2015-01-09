<?php
namespace DF\Paginator\Adapter;

use \Doctrine\ORM\Query;
use \Doctrine\ORM\QueryBuilder;

class DoctrineQuery implements \Zend_Paginator_Adapter_Interface
{
    protected $_query;
    protected $_rowCount;

    public function __construct($query)
    {
        if ($query instanceof QueryBuilder)
            $query = $query->getQuery();
        
        $this->_query = $query;
    }
    
    public function cloneQuery(Query $query)
    {
        $countQuery = clone $query;
        $params = $query->getParameters();

        foreach ($params as $key => $param) {
            $countQuery->setParameter($key, $param);
        }

        return $countQuery;
    }

    public function getItems($offset, $itemsPerPage)
    {
        $qb = $this->cloneQuery($this->_query);
        
        $qb->setFirstResult($offset);
        $qb->setMaxResults($itemsPerPage);
        
        return $qb->execute();
    }
    
    public function count()
    {
        if ($this->_rowCount === null)
        {
            $query = $this->cloneQuery($this->_query);
            
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('DF\Doctrine\Paginate\CountWalker'));
            $query->setFirstResult(null)->setMaxResults(null);
            
            $this->_rowCount = $query->getSingleScalarResult();
        }

        return $this->_rowCount;
    }
}