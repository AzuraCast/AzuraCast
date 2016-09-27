<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\User;

class ManagersController extends BaseController
{
    /* TODO: Finish Implementation */

    public function addadminAction()
    {
        $this->doNotRender();

        $email = $this->getParam('email');

        $user = $this->em->getRepository(User::class)->getOrCreate($email);
        $user->stations->add($this->station);

        $this->em->persist($user);
        $this->em->flush();

        /** @var \App\Messenger $messenger */
        $messenger = $this->di['messenger'];

        $messenger->send(array(
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

        $user = $this->em->getRepository(User::class)->find($id);
        $user->stations->removeElement($this->station);

        $this->em->persist($user);
        $this->em->flush();

        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }
}