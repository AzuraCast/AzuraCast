<?php
namespace Modules\Frontend\Controllers;

use \Entity\User;
use \Entity\UserExternal;

class ProfileController extends BaseController
{
    public function indexAction()
    {
        $user = $this->auth->getLoggedInUser();
        $this->view->user = $user;

        $form = new \App\Form($this->current_module_config->forms->profile);

        $user_profile = $user->toArray($this->em);
        unset($user_profile['auth_password']);
        $form->setDefaults($user_profile);

        $this->view->form = $form;
    }

    public function editAction()
    {
        $this->acl->checkPermission('is logged in');

        $user = $this->auth->getLoggedInUser();
        $form = new \App\Form($this->current_module_config->forms->profile);

        $user_profile = $user->toArray($this->em);
        unset($user_profile['auth_password']);

        /*
        $user_profile['customization'] = array_merge(\App\Customization::getDefaults(), $user_profile['customization']);
        unset($user_profile['auth_password']);
        */

        $form->setDefaults($user_profile);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $user->fromArray($this->em, $data);
            $user->save();

            /*
            foreach($data['customization'] as $custom_key => $custom_val)
                \App\Customization::set($custom_key, $custom_val);
            */

            $this->alert('Profile saved!', 'green');
            return $this->redirectFromHere(array('action' => 'index'));
        }

        return $this->renderForm($form, 'edit', 'Edit Profile');
    }

    public function timezoneAction()
    {
        $form = new \App\Form($this->current_module_config->forms->timezone);
        $form->setDefaults(array(
            'timezone'      => \App\Customization::get('timezone'),
        ));

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            \App\Customization::set('timezone', $data['timezone']);

            $this->alert('Time zone updated!', 'green');
            $this->redirectToStoredReferrer('customization');
            return;
        }

        $this->storeReferrer('customization');

        $this->view->setVar('title', 'Set Time Zone');
        $this->renderForm($form);
    }
}
