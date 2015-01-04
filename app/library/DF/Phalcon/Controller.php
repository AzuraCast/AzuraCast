<?php
namespace DF\Phalcon;

class Controller extends \Phalcon\Mvc\Controller
{
    public function getParam($param_name, $default_value = NULL)
    {
        if ($this->hasParam($param_name))
            return $this->request->get($param_name);
        else
            return $default_value;
    }

    public function _getParam($param_name, $default_value = NULL)
    {
        return $this->getParam($param_name, $default_value);
    }

    public function hasParam($param_name)
    {
        return $this->request->has($param_name);
    }

    public function render($template_name = NULL)
    {
        if (!is_null($template_name))
            $this->view->pick($template_name);
    }
    public function doNotRender()
    {
        $this->view->disable();
    }


}