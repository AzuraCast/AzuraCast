<?php
namespace App\Mvc;

class View extends \League\Plates\Engine
{
    protected $rendered = false;
    protected $disabled = false;

    public function disable()
    {
        $this->disabled = true;
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    public function isRendered()
    {
        return $this->rendered;
    }

    public function __set($key, $value)
    {
        $this->addData([$key => $value]);
    }

    public function __get($key)
    {
        return $this->getData($key);
    }

    public function render($name, array $data = array())
    {
        if (!$this->isDisabled())
        {
            $this->rendered = true;
            return parent::render($name, $data);
        }
        return null;
    }

}