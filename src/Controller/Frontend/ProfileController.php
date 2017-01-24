<?php
namespace Controller\Frontend;

use \Entity\User;
use \Entity\UserExternal;

class ProfileController extends BaseController
{
    public function indexAction()
    {
        $user = $this->auth->getLoggedInUser();
        $this->view->user = $user;

        $form = new \App\Form($this->config->forms->profile);

        $user_profile = $user->toArray($this->em);
        unset($user_profile['auth_password']);
        $form->setDefaults($user_profile);

        $this->view->form = $form;
    }

    public function editAction()
    {
        $this->acl->checkPermission('is logged in');

        $user = $this->auth->getLoggedInUser();
        $form = new \App\Form($this->config->forms->profile);

        $user_profile = $user->toArray($this->em);
        unset($user_profile['auth_password']);

        $form->setDefaults($user_profile);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();
            $user->fromArray($this->em, $data);

            $this->em->persist($user);
            $this->em->flush();

            $this->alert(_('Profile saved!'), 'green');
            return $this->redirectFromHere(array('action' => 'index'));
        }

        return $this->renderForm($form, 'edit', _('Edit Profile'));
    }
}