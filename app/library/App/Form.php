<?php
namespace App;
use App\Forms\NibbleForm;

/**
 * A helper class that extends allows flatfile configuration form management.
 *
 * Class Form
 * @package App
 */
class Form
{
    /**
     * @var Forms\NibbleForm
     */
    protected $form;

    /**
     * @var array
     */
    protected $options;

    /**
     * Form constructor.
     * @param $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof \Zend\Config\Config)
            $options = $options->toArray();

        // Clean up options.
        $this->options = $this->_cleanUpConfig($options);

        $form_name = $options['name'] ?: 'app_form';
        $form_action = $options['action'] ?: '';

        $this->form = new NibbleForm($form_action);
        $this->form->setName($form_name);

        $this->_setUpForm();
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function setDefaults($data)
    {
        $this->populate($data);
    }

    public function populate($data)
    {
        foreach($data as $field_name => &$field_value)
        {
            $field = $this->form->getField($field_name);

            if ($field instanceof \Nibble\NibbleForms\Field\Radio ||
                $field instanceof \Nibble\NibbleForms\Field\Checkbox)
            {
                if ($field_value === "")
                    $field_value = '0';
            }
        }

        $this->form->addData($data);
    }

    public function isValid()
    {
        return $this->form->validate();
    }

    public function getValues()
    {
        $values = array();

        foreach($this->options['groups'] as $fieldset)
        {
            foreach($fieldset['elements'] as $element_id => $element_info)
                $values[$element_id] = $this->form->getData($element_id);
        }

        return $values;
    }

    protected function _cleanUpConfig($options)
    {
        if (empty($options['groups']))
            $options['groups'] = array();

        $options['groups'][] = ['elements' => $options['elements']];
        return $options;
    }

    protected function _setUpForm()
    {
        foreach($this->options['groups'] as $group_id => $group_info)
        {
            foreach($group_info['elements'] as $element_name => $element_info)
                $this->_setUpElement($element_name, $element_info);
        }
    }

    protected function _setUpElement($element_name, $element_info)
    {
        $field_type = strtolower($element_info[0]);
        $field_options = $element_info[1];

        $field_type_lookup = [
            'checkboxes' => 'checkbox',
            'multicheckbox' => 'checkbox',
            'textarea'  => 'textArea',
        ];

        $defaults = [
            'required' => false,
        ];
        $field_options = array_merge($defaults, $field_options);

        if (isset($field_type_lookup[$field_type]))
            $field_type = $field_type_lookup[$field_type];

        if ($field_type == 'submit')
            return null;

        if (!empty($field_options['multiOptions']))
            $field_options['choices'] = $field_options['multiOptions'];
        unset($field_options['multiOptions']);

        if (!empty($field_options['options']))
            $field_options['choices'] = $field_options['options'];
        unset($field_options['options']);

        if (isset($field_options['default']))
            $this->form->addData([$element_name => (string)$field_options['default']]);
        unset($field_options['default']);

        unset($field_options['description']);

        $this->form->addField($element_name, $field_type, $field_options);
    }
}