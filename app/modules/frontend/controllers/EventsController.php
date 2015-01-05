<?php
namespace Modules\Frontend\Controllers;

class EventsController extends BaseController
{
    public function indexAction()
    {
        $this->redirectToRoute(array('module' => 'convention', 'action' => 'index'));
    }

    public function scheduleAction()
    {
        $this->redirectFromHere(array('controller' => 'schedule', 'action' => 'index'));
    }
}