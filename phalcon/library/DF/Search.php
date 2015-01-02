<?php
namespace DF;
class Search
{
    protected $_query;
    protected $_fields;

    public function  __construct($from)
    {
        if (is_array($from))
        {
            $this->_query = Doctrine_Query::create();
            foreach($from as $class)
            {
                $this->_query->from($from);
            }
        }
        else
        {
            $this->_query = Doctrine_Query::create()->from($from);
        }
    }

    public function setSearchFields($fields)
    {
        $this->_fields = $fields;
    }

    public function setSearchTerms($searchterms)
    {
        if (empty($searchterms))
        {
            \DF\Flash::addMessage('Please enter at least one search term.');
            return false;
        }
        $quoted = array();
        // get quoted strings from search terms
        preg_match_all('/\"[^"]+\"/', $searchterms, $allmatches);
        // pull out from other terms
        if (count($allmatches) > 0)
        {
            $flat = array();
            foreach($allmatches as $matchset)
            {
                $flat = array_merge($flat,array_unique($matchset));
            }
            $quoted = str_replace(array('"'),'',$flat);
            $searchterms = preg_replace('/\"[^"]+\"/', '', $searchterms);
        }
        // break apart search terms
        $terms = array_filter(explode(' ',$searchterms));
        // combine with quoted strings
        $terms = array_merge($terms,$quoted);

        if (is_array($this->_fields))
        {
            $fields = $this->_fields;
        }
        else
        {
            $fields = $this->_query->getFieldNames();
        }

        $ors = array();
        foreach($fields as $field)
        {
            foreach($terms as $term)
            {
                $ors[] = "($field LIKE '%$term%')";
            }
        }
        $this->_query->addWhere('('.implode(' OR ', $ors).')');
        
        return true;
    }

    public function setSort($sortfields)
    {
        if (is_array($sortfields))
        {
            foreach($sortfields as $sort)
            {
                $this->_query->addOrderBy($sort);
            }
        }
    }
    
    public function excludeDeleted()
    {
        $this->_query->addWhere('deleted_at IS NULL');
    }

    public function includeIds(Array $id_array)
    {
        //$list = implode(',',$id_array);
        $this->_query->addWhereIn('id', $id_array);
    }

    public function execute($page = 1, $per_page = -1)
    {
        if( $per_page < 1 )
        {
            return array(
                'pager' => null,
                'results' => $this->_query->execute(),
            );
        }
        else
        {
            $pager = new Doctrine_Pager($this->_query, $page, $per_page);

            return array(
                'pager' => $pager,
                'results' => $pager->execute(),
            );
        }
    }
}