<?php
namespace Modules\Stations\Controllers;

use Entity\Station;

class ManagersController extends BaseController
{
    /* TODO: Finish Implementation */

    public function addadminAction()
    {
        $this->doNotRender();

        $email = $this->getParam('email');
        $user = \Entity\User::getOrCreate($email);

        $user->stations->add($this->station);
        $user->save();

        \App\Messenger::send(array(
            'to' => $user->email,
            'subject' => 'Access Granted to Station Center',
            'template' => 'newperms',
            'vars' => array(
                'areas' => array('Station Center: '.$this->station->name),
            ),
        ));

        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'email' => NULL));
    }

    public function removeadminAction()
    {
        $this->doNotRender();

        $id = (int)$this->getParam('id');

        $user = \Entity\User::find($id);
        $user->stations->removeElement($this->station);
        $user->save();

        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }
}