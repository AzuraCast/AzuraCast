<?php
namespace App\Forms\Element;

use Nibble\NibbleForms\Field;

class Markup extends Field
{
    public $error = array();

    protected $label;
    protected $markup;

    public function __construct($label = 'CAPTCHA', $attributes = array())
    {
        $this->label = $label;
        $this->markup = $attributes['markup'];
    }

    public function returnField($form_name, $name, $value = '')
    {
        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label for="%s">%s</label>', $name, $this->label),
            'field' => $this->markup,
            'html' => $this->html
        );
    }

    public function validate($val)
    {
        return true;
    }
}