<?php
namespace DF\Forms\Element;

class UnixDate extends \Phalcon\Forms\Element\Date implements \Phalcon\Forms\ElementInterface
{
    public function setDefault($value)
    {
        parent::setDefault(gmdate('Y-m-d', $value));
    }

    public function getValue($return_mode = false)
    {
        if ($return_mode)
        {
            return \DF\Utilities::gstrtotime($this->_value.' 00:00:00');
        }
        else
        {
            return parent::getValue();
        }
    }

}