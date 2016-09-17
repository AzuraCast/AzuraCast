<?php
namespace App\Forms\Element;

class Csrf extends \Phalcon\Forms\Element\Hidden implements \Phalcon\Forms\ElementInterface
{
    protected $_csrf;

    public function __construct($name, $attributes = null)
    {
        $di = $GLOBALS['di'];
        $this->_csrf = $di['csrf'];

        parent::__construct($name, $attributes);
    }

    public function render($attributes = array())
    {
        $attributes = (array)$attributes;
        $attributes['value'] = $this->_csrf->generate('form');

        return parent::render($attributes);
    }

    public function renderView()
    {
        return '';
    }
}