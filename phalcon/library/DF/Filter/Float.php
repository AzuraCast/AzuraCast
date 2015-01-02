<?php
namespace DF\Filter;
class Float implements \Zend_Filter_Interface
{
    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns (float) $value
     *
     * @param  string $value
     * @return float
     */
    public function filter($value)
    {
        $value = (string)$value;
        $value = preg_replace('#[^0-9\.\-]#', '', $value);
        
        return (float)$value;
    }
}