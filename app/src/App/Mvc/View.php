<?php
namespace App\Mvc;

use Interop\Container\ContainerInterface;
use League\Plates\Template\Data;

class View extends \League\Plates\Engine
{
    protected $rendered = false;

    protected $disabled = false;

    public function reset()
    {
        $this->rendered = false;
        $this->disabled = false;
        $this->data = new Data();
    }

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

    public function render($name, array $data = [])
    {
        if (!$this->isDisabled()) {
            $this->rendered = true;

            return parent::render($name, $data);
        }

        return null;
    }

    public function fetch($name, array $data = [])
    {
        return parent::render($name, $data);
    }

    public function setFolder($name, $directory, $fallback = false)
    {
        if ($this->folders->exists($name)) {
            $this->folders->remove($name);
        }

        $this->folders->add($name, $directory, $fallback);

        return $this;
    }
}