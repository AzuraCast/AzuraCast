<?php
namespace Controller\Frontend;

use Entity;

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

        /** @var Entity\User $user */
        $user = $this->auth->getLoggedInUser();

        $form_config = $this->config->forms->profile->toArray();
        $form_config['groups']['reset_password']['elements']['password'][1]['validator'] = function($val, \Nibble\NibbleForms\Field $field) use ($user) {
            $form = $field->getForm();

            $new_password = $form->getData('new_password');
            if (!empty($new_password)) {
                if ($user->verifyPassword($val)) {
                    return true;
                } else {
                    $field->error[] = 'Current password could not be verified.';
                    return false;
                }
            }

            return true;
        };

        $form = new \App\Form($form_config);

        $user_profile = $user->toArray($this->em);
        unset($user_profile['auth_password']);

        $form->setDefaults($user_profile);

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            $user->fromArray($this->em, $data);

            if (!empty($data['new_password']))
            {
                $user->setAuthPassword($data['new_password']);
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->alert(_('Profile saved!'), 'green');

            return $this->redirectFromHere(['action' => 'index']);
        }

        return $this->renderForm($form, 'edit', _('Edit Profile'));
    }
}