<?php
namespace DF\View\Helper;

class HelperAbstract
{
    protected $di;
    protected $view;

    public function __construct($di)
    {
        $this->di = $di;
        $this->view = $di->get('view');
    }
}