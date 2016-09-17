<?php
namespace App\Mvc;

class View extends \League\Plates\Engine
{
    protected $disabled = false;
    public function disable()
    {
        $this->disabled = true;
    }

    public function __set($key, $value)
    {
        $this->addData([$key => $value]);
    }

    public function __get($key)
    {
        return $this->getData($key);
    }
}