<?php
namespace DF\Forms\Element;

class MultiCheckbox extends \Phalcon\Forms\Element implements \Phalcon\Forms\ElementInterface
{
    protected $_inner_form;
    protected $_field_options;

    public function __construct($name, $options = array(), $attributes = null)
    {
        parent::__construct($name, $attributes);

        $this->_inner_form = new \Phalcon\Forms\Form();
        $this->_field_options = (array)$options;

        foreach($this->_field_options as $check_key => $check_val)
        {
            $field_options = array(
                'name'      => $name.'[]',
                'value'     => $check_key,
            );

            $element = new \Phalcon\Forms\Element\Check($name.'_'.$check_key, $field_options);
            $element->setLabel($check_val);

            $this->_inner_form->add($element);
        }
    }

    public function getElements()
    {
        return $this->_inner_form->getElements();
    }

    public function setDefault($value)
    {
        foreach((array)$value as $value_key)
        {
            if (isset($this->_field_options[$value_key]))
            {
                $element = $this->_inner_form->get($this->getName().'_'.$value_key);
                $element->setDefault($value_key);
            }
        }
    }

    public function getValue()
    {
        $elements = $this->_inner_form->getElements();
        $result = array();

        foreach($elements as $element)
        {
            $element_value = $element->getValue();

            if ($element_value !== null)
                $result[] = $element_value;
        }

        return $result;
    }

    public function render($attributes=null)
    {
        $elements = $this->_inner_form->getElements();
        $render_raw = array();

        foreach($elements as $element)
        {
            $render_raw[] = $element->render($attributes);
        }

        return implode('<br>', $render_raw);
    }

    public function clear()
    {
        foreach($this->_inner_form->getElements() as $element)
            $element->clear();
    }
}