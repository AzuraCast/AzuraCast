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
        $set_data = [];

        // Flatten arrays that may be multidimensional.
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($data));
        foreach($it as $field_name => $field_value)
        {
            if ($this->form->checkField($field_name))
            {
                $field = $this->form->getField($field_name);

                if ($field instanceof \Nibble\NibbleForms\Field\Radio ||
                    $field instanceof \Nibble\NibbleForms\Field\Checkbox)
                {
                    if ($field_value === "")
                        $field_value = '0';
                }

                $set_data[$field_name] = $field_value;
            }
        }

        $this->form->addData($set_data);
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
            {
                $value = $this->form->getData($element_id);

                if (!empty($element_info[1]['belongsTo']))
                    $values[$element_info[1]['belongsTo']][$element_id] = $value;
                else
                    $values[$element_id] = $value;
            }
        }

        return $values;
    }

    public function getValue($key)
    {
        return $this->form->getData($key);
    }

    protected function _cleanUpConfig($options)
    {
        if (empty($options['groups']))
            $options['groups'] = array();

        if (!empty($options['elements']))
        {
            $options['groups'][] = ['elements' => $options['elements']];
            unset($options['elements']);
        }

        // Standardize some field input.
        $field_type_lookup = [
            'checkboxes' => 'checkbox',
            'multicheckbox' => 'checkbox',
            'multiselect' => 'multipleSelect',
            'textarea'  => 'textArea',
        ];

        foreach($options['groups'] as &$group)
        {
            foreach($group['elements'] as &$element)
            {
                if (!empty($element[1]['label']) && substr($element[1]['label'], -1) !== ':')
                    $element[1]['label'] = $element[1]['label'].':';

                $element[0] = strtolower($element[0]);
                if (isset($field_type_lookup[$element[0]]))
                    $element[0] = $field_type_lookup[$element[0]];

                if (!empty($element[1]['multiOptions']))
                    $element[1]['choices'] = $element[1]['multiOptions'];
                unset($element[1]['multiOptions']);

                if (!empty($element[1]['options']))
                    $element[1]['choices'] = $element[1]['options'];
                unset($element[1]['options']);
            }
        }

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

        $defaults = [
            'required' => false,
        ];
        $field_options = array_merge($defaults, $field_options);

        if ($field_type == 'submit')
            return null;

        if (isset($field_options['default']))
            $this->form->addData([$element_name => (string)$field_options['default']]);
        unset($field_options['default']);

        unset($field_options['description']);

        $this->form->addField($element_name, $field_type, $field_options);
    }
}