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
        return parent::__construct($action, $submit_value, $html5, $method, $sticky, $message_type, $format,
            $multiple_errors);
    }

    /**
     * @inheritdoc
     */
    public function addField($field_name, $type = 'text', array $attributes = [], $overwrite = false)
    {
        $namespace_options = [
            "\\App\\Forms\\Element\\" . ucfirst($type),
            "\\Nibble\\NibbleForms\\Field\\" . ucfirst($type),
        ];

        foreach ($namespace_options as $namespace_option) {
            if (class_exists($namespace_option)) {
                $namespace = $namespace_option;
                break;
            }
        }

        if (!isset($namespace)) {
            return false;
        }

        if (isset($attributes['label'])) {
            $label = $attributes['label'];
        } else {
            $label = ucfirst(str_replace('_', ' ', $field_name));
        }

        $field_name = \Nibble\NibbleForms\Useful::slugify($field_name, '_');

        if (isset($this->fields->$field_name) && !$overwrite) {
            return false;
        }

        $this->fields->$field_name = new $namespace($label, $attributes);
        $this->fields->$field_name->setForm($this);

        return $this->fields->$field_name;
    }

    public function getField($key)
    {
        return $this->fields->$key;
    }

    public function validate()
    {
        $request = strtoupper($this->method) == 'POST' ? $_POST : $_GET;
        if (isset($request[$this->name])) {
            $this->data = $request[$this->name];
        }

        return parent::validate();
    }
}