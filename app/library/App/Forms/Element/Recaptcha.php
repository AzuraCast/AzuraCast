<?php
namespace App\Forms\Element;

class Recaptcha extends \Phalcon\Forms\Element implements \Phalcon\Forms\ElementInterface
{
    protected $config;

    public function __construct($name, $attributes=null)
    {
        parent::__construct($name, $attributes);

        $di = $GLOBALS['di'];
        $config = $di->get('config');
        $apis_config = $config->apis->toArray();

        if (empty($apis_config) || !isset($apis_config['recaptcha']))
            throw new \App\Exception('Recaptcha is not configured in apis.conf.php!');

        $this->config = $apis_config['recaptcha'];
    }

    public function render($attributes = null)
    {
        $return = '';
        $return .= '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
        $return .= '<div class="g-recaptcha" data-sitekey="'.$this->config['public_key'].'" data-theme="dark"></div>';
        return $return;
    }
}