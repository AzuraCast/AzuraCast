<?php
namespace DF\View\Helper;

class HelperAbstract
{
    protected $di;
    protected $view;
    protected $viewHelper;

    public function __construct(\Phalcon\DiInterface $di)
    {
        $this->di = $di;
        $this->view = $di->get('view');
        $this->viewHelper = $di->get('viewHelper');
    }
}