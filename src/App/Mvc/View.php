<?php
namespace App\Mvc;

use Interop\Container\ContainerInterface;
use League\Plates\Template\Data;

class View extends \League\Plates\Engine
{
    /**
     * Add "View Helpers" for common functions.
     */
    public function addAppCommands(ContainerInterface $di)
    {
        $this->loadExtension(new View\Paginator($di['url']));

        $this->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ";&#x"), 3, -3);
            $link_text = (is_null($link_text)) ? $address : $link_text;

            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });

        $this->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num == 1) {
                return $word;
            } else {
                return \Doctrine\Common\Inflector\Inflector::pluralize($word);
            }
        });

        $this->registerFunction('truncate', function ($text, $length = 80) {
            return \App\Utilities::truncate_text($text, $length);
        });
    }

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