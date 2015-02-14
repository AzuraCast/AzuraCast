<?php
namespace Modules\Stations\Controllers;

class SubmitController extends \DF\Phalcon\Controller
{
    public function indexAction()
    {
        $this->redirectToRoute(array('module' => 'frontend', 'controller' => 'submit', 'action' => 'station'));
    }
}