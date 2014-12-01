<?php

namespace Phalcon\Frontend\Controllers;

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        $view = $this->di->get('view');
        print_r($view);
        exit;

        echo 'Test';
    }

}

