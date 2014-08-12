<?php
namespace PVL\Controller\Action;

class Mobile extends \DF\Controller\Action
{
    public function init()
    {
        parent::init();

        // Hard-code base URL (for mobile Android app).
        $config = $this->config;

        if ($config->application->base_url)
        {
            $base_url = $config->application->base_url;
            $base_url = ((DF_IS_SECURE) ? 'https:' : 'http:').$base_url;

            \DF\Url::setBaseUrl($base_url);
        }

        // Set mobile template.
        \Zend_Layout::getMvcInstance()->setLayout('mobile');

        header("Access-Control-Allow-Origin: *");
    }

    public function preDispatch()
    {
        parent::preDispatch();

        \Zend_Layout::getMvcInstance()->enableLayout();
    }
}