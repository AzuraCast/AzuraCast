<?php
namespace DF\Phalcon\Service;

class ViewHelper implements \Phalcon\DI\InjectionAwareInterface
{
    protected $_di;
    public function setDi(\Phalcon\DiInterface $di)
    {
        $this->_di = $di;
    }
    public function getDi()
    {
        return $this->_di;
    }

    public function __call($function, $args)
    {
        $class_name = '\App\View\Helper\\'.ucfirst($function);

        if (class_exists($class_name))
        {
            $class_instance = new $class_name($this->_di);
            return call_user_func_array(array($class_instance, $function), $args);
        }
        else
        {
            throw new \Exception('View helper not found: '.$function);
        }
    }

}