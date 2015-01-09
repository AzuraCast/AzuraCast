<?php
namespace DF\Forms\Element;

class Markup extends \Phalcon\Forms\Element implements \Phalcon\Forms\ElementInterface
{
    protected $markup;

    public function __construct($name, $markup=null, $attributes=null)
    {
        parent::__construct($name, $attributes);

        $this->markup = $markup;
    }

    public function render($attributes=null)
    {
        return $this->markup;
    }
}