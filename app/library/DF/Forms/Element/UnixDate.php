<?php
namespace DF\Forms\Element;

class UnixDate extends \Phalcon\Forms\Element\Date implements \Phalcon\Forms\ElementInterface
{
    public function setDefault($value)
    {
        parent::setDefault(gmdate('Y-m-d', $value));
    }

    public function processValue($post_value)
    {
        return \DF\Utilities::gstrtotime($post_value.' 00:00:00');
    }

}