<?php
namespace App\Forms;

class NibbleForm extends \Nibble\NibbleForms\NibbleForm
{
    public function __construct(
        $action = '',
        $submit_value = 'Submit',
        $html5 = true,
        $method = 'post',
        $sticky = true,
        $message_type = 'list',
        $format = 'list',
        $multiple_errors = false
    ) {
        return parent::__construct($action, $submit_value, $html5, $method, $sticky, $message_type, $format, $multiple_errors);
    }

    public function getField($key)
    {
        return $this->fields->$key;
    }


}