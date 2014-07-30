<?php
class EventsController extends \DF\Controller\Action
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