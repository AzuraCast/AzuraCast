<?php
namespace App;

use Nibble\NibbleForms\NibbleForm;

/**
 * A helper class that extends allows flatfile configuration form management.
 *
 * Class Form
 * @package App
 */
class Form
{
    /**
     * @var NibbleForm
     */
    protected $form;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array An array of "belongsTo" lookup groups (for processing data).
     */
    protected $groups;

    /**
     * Form constructor.
     * @param $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof \Zend\Config\Config) {
            $options = $options->toArray();
        }

        // Clean up options.
        $this->groups = [];
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

        foreach ((array)$data as $row_key => $row_value) {
            if (is_array($row_value) && isset($this->groups[$row_key])) {
                foreach ($row_value as $row_subkey => $row_subvalue) {
                    $set_data[$row_key . '_' . $row_subkey] = $row_subvalue;
                }
            } else {
                $set_data[$row_key] = $row_value;
            }
        }

        foreach ($set_data as $field_name => $field_value) {
            if ($this->form->checkField($field_name)) {
                $field = $this->form->getField($field_name);

                if ($field instanceof \Nibble\NibbleForms\Field\Radio ||
                    $field instanceof \Nibble\NibbleForms\Field\Checkbox
                ) {
                    if ($field_value === "") {
                        $field_value = '0';
                    }
                }

                $set_data[$field_name] = $field_value;
            }
        }

        $this->form->addData($set_data);
    }

    public function isValid($request = null)
    {
        return $this->form->validate($request);
    }

    public function getValues()
    {
        $values = [];

        foreach ($this->options['groups'] as $fieldset) {
            foreach ($fieldset['elements'] as $element_id => $element_info) {
                if (!empty($element_info[1]['belongsTo'])) {
                    $group = $element_info[1]['belongsTo'];
                    $values[$group][$element_id] = $this->form->getData($group . '_' . $element_id);
                } else {
                    $values[$element_id] = $this->form->getData($element_id);
                }
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
        if (empty($options['groups'])) {
            $options['groups'] = [];
        }

        if (!empty($options['elements'])) {
            $options['groups'][] = ['elements' => $options['elements']];
            unset($options['elements']);
        }

        // Standardize some field input.
        $field_type_lookup = [
            'checkboxes' => 'checkbox',
            'multicheckbox' => 'checkbox',
            'multiselect' => 'multipleSelect',
            'textarea' => 'textArea',
        ];

        foreach ($options['groups'] as &$group) {
            foreach ($group['elements'] as &$element) {
                $element[0] = strtolower($element[0]);
                if (isset($field_type_lookup[$element[0]])) {
                    $element[0] = $field_type_lookup[$element[0]];
                }

                if (!empty($element[1]['multiOptions'])) {
                    $element[1]['choices'] = $element[1]['multiOptions'];
                }
                unset($element[1]['multiOptions']);

                if (!empty($element[1]['options'])) {
                    $element[1]['choices'] = $element[1]['options'];
                }
                unset($element[1]['options']);
            }
        }

        return $options;
    }

    protected function _setUpForm()
    {
        foreach ($this->options['groups'] as $group_id => $group_info) {
            foreach ($group_info['elements'] as $element_name => $element_info) {
                $this->_setUpElement($element_name, $element_info);
            }
        }
    }

    protected function _setUpElement($element_name, $element_info)
    {
        $field_type = $element_info[0];
        $field_options = $element_info[1];

        if (!empty($field_options['belongsTo'])) {
            $group = $field_options['belongsTo'];
            $this->groups[$group][] = $element_name;

            $element_name = $group . '_' . $element_name;
        }

        $defaults = [
            'required' => false,
        ];
        $field_options = array_merge($defaults, $field_options);

        if ($field_type == 'submit') {
            return null;
        }

        if (isset($field_options['default'])) {
            $this->form->addData([$element_name => (string)$field_options['default']]);
        }

        unset($field_options['default']);
        unset($field_options['description']);

        $this->form->addField($element_name, $field_type, $field_options);
    }

    /**
     * Return a UTC-localized time code given a HTML5 time input's return value.
     *
     * @param $input_time
     * @return int
     */
    public static function getTimeCode($input_time): int
    {
        $dt = \DateTime::createFromFormat('!G:i', $input_time);
        $dt->setTimezone(new \DateTimeZone('UTC'));

        return (int)$dt->format('Gi');
    }

    /**
     * Get a Unix timestamp from a given date from an HTML5 date input.
     *
     * @param $input_date
     * @return int
     */
    public static function getTimestampFromDate($input_date): int
    {
        $dt = \DateTime::createFromFormat('!Y-m-d', $input_date, new \DateTimeZone('UTC'));
        return (int)$dt->getTimestamp();
    }
}