<?php
namespace App\Forms;

class NibbleForm extends \Nibble\NibbleForms\NibbleForm
{
    protected $filters;
    protected $validators;

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
        $this->filters = [];
        $this->validators = [];

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

        if (!empty($attributes['filter'])) {
            $this->filters[$field_name] = $attributes['filter'];
            unset($attributes['filter']);
        }
        if (!empty($attributes['validator'])) {
            $this->validators[$field_name] = $attributes['validator'];
            unset($attributes['validator']);
        }

        $this->fields->$field_name = new $namespace($label, $attributes);
        $this->fields->$field_name->setForm($this);

        return $this->fields->$field_name;
    }

    public function getField($key)
    {
        return $this->fields->$key;
    }

    public function getData($key)
    {
        if (isset($this->filters[$key]) && is_callable($this->filters[$key])) {
            return $this->filters[$key]($this->data[$key] ?? false);
        }

        return $this->data[$key] ?? false;
    }

    public function validate($request = null)
    {
        if ($request === null) {
            $request = strtoupper($this->method) == 'POST' ? $_POST : $_GET;
        }

        if (isset($request[$this->name])) {
            $this->data = $request[$this->name];
            $form_data = $request[$this->name];
        } else {
            $this->valid = false;
            return false;
        }

        // Check CSRF token.
        if ((isset($_SESSION["nibble_forms"]["_crsf_token"], $_SESSION["nibble_forms"]["_crsf_token"][$this->name])
                && $form_data["_crsf_token"] !== $_SESSION["nibble_forms"]["_crsf_token"][$this->name])
            || !isset($_SESSION["nibble_forms"]["_crsf_token"])
            || !isset($form_data["_crsf_token"])
        ) {
            $title = preg_replace('/_/', ' ', ucfirst('CRSF error'));
            if ($this->message_type == 'list') {
                $this->messages[] = array('title' => $title, 'message' => ucfirst('CRSF token invalid'));
            }

            $this->valid = false;
        }

        $_SESSION["nibble_forms"]["_crsf_token"] = array();

        foreach ($this->fields as $key => $value) {
            if (!$value->validate($form_data[$key] ?? $_FILES[$this->name][$key] ?? '')) {
                $this->valid = false;
                return false;
            }
        }

        foreach($this->validators as $key => $validator) {
            if (!$validator($this->data[$key] ?? $_FILES[$this->name][$key] ?? '')) {
                $this->fields->$key->error[] = 'Invalid data';

                $this->valid = false;
                return false;
            }
        }

        return $this->valid;
    }
}