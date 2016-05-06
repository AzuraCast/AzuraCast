<?php
namespace App\Forms\Element;

class MultiCheckbox extends \Phalcon\Forms\Element implements \Phalcon\Forms\ElementInterface
{
    protected $_field_options;

    public function __construct($name, $options = array(), $attributes = null)
    {
        parent::__construct($name, $attributes);

        $this->_inner_form = new \Phalcon\Forms\Form();
        $this->_field_options = (array)$options;
    }

    public function getCheckboxes()
    {
        $value = $this->getValue();
        $checkboxes = array();

        if (!is_array($value))
            $value = array($value);

        foreach($this->_field_options as $check_key => $check_val)
        {
            $field_options = array($this->_name.'[]');
            $field_options['value'] = $check_key;
            $field_options['id'] = $this->_name.'_'.$check_key;

            if (in_array($check_key, $value))
                $field_options['checked'] = 'checked';

            $checkbox_code = \Phalcon\Tag::checkField($field_options);
            $checkboxes[] = '<label>'.$checkbox_code.' '.$check_val.'</label>';
        }

        return $checkboxes;
    }

    public function render($attributes=null)
    {
        $checkboxes = $this->getCheckboxes();
        return implode('<br>', $checkboxes);
    }
}