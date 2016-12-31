<?php
namespace App\Forms\Element;

use Nibble\NibbleForms\Field;

class Captcha extends Field
{
    public $error = array();

    protected $label;
    protected $attributes;

    public function __construct($label = 'CAPTCHA', $attributes = array())
    {
        $this->label = $label;
        $this->attributes = $attributes;
    }

    public function returnField($form_name, $name, $value = '')
    {
        $field = <<<FIELD
<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
<div class="g-recaptcha" data-sitekey="%S" data-theme="dark"></div>
FIELD;

        $class = !empty($this->error) ? ' class="error"' : '';

        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label for="%s"%s>%s</label>', $name, $class, $this->label),
            'field' => sprintf($field, $this->attributes['public_key']),
            'html' => $this->html
        );
    }

    public function validate($val)
    {
        $params = array(
            'secret' => $this->attributes['private_key'],
            'response' => $val,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        );

        $url = 'https://www.google.com/recaptcha/api/siteverify?' .http_build_query($params);
        $response = json_decode(file_get_contents($url));
        return $response->success;
    }

}
