<?php
namespace DF\Doctrine\Filter;
use Doctrine\ORM\Mapping\ClassMetaData,
    Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDelete extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!isset($targetEntity->fieldMappings['deleted_at']))
            return '';
            
        // Check for whether filter is being called from within a proxy and exempt from filter if so.
        $has_proxy = FALSE;
        $backtrace = debug_backtrace();
        foreach($backtrace as $log)
        {
            if (stristr($log['class'], 'Proxy') !== FALSE)
                $has_proxy = TRUE;
        }
        
        if ($has_proxy)
            return '';
        else
            return $targetTableAlias.'.deleted_at IS NULL';
    }
}