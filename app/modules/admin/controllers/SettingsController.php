<?php
namespace Modules\Admin\Controllers;

use Entity\Settings;
use Entity\SettingsRepository;

class SettingsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer settings');
    }
    
    public function indexAction()
    {
        /** @var SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Settings::class);

        $form = new \App\Form($this->current_module_config->forms->settings->form);

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