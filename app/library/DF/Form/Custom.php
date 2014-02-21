<?php
namespace DF\Form;
class Custom extends \DF\Form
{
    private $_parameters;
    
    public function setParameters(array $parameters)
    {
        foreach( $parameters as $key => $value )
            $this->setParameter((string)$key, $value);
        
        return $this;
    }
    
    public function setParameter($key, $value)
    {
        $this->_parameters[(string)$key] = $value;
        
        return $this;
    }
    
    public function getParameter($key, $default = null)
    {
        if( isset($this->_parameters[(string)$key]) )
            return $this->_parameters[(string)$key];
        else
            return $default;
    }
    
    public function getParameters()
    {
        return (array)$this->_parameters;
    }
    
    public function save()
    {
        throw new \DF\Exception\Warning("Save function not implemented.");
    }
}