<?php
namespace Controller\Admin;

use Entity\Settings;
use Entity\Repository;

class SettingsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer settings');
    }
    
    public function indexAction()
    {
        /** @var Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Settings::class);

        $form = new \App\Form($this->config->forms->settings->form);

        $existing_settings = $settings_repo->fetchArray(FALSE);
        $form->setDefaults($existing_settings);
        
        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();
            $settings_repo->setSettings($data);

            $this->alert(_('Changes saved.'), 'green');
            return $this->redirectHere();
        }

        return $this->renderForm($form, 'edit', _('Site Settings'));
    }
}