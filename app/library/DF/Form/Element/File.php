<?php
/**
 * UNIX Timestamp Form Element
 */

namespace DF\Form\Element;
class File extends \Zend_Form_Element_File
{
    protected $_original_value;
    
    public function setValue($value)
    {
        $this->_original_value = $value;
    }
    public function getOriginalValue()
    {
        return $this->_original_value;
    }
}