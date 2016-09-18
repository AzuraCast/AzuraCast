<?php
namespace Modules\Admin\Controllers;

use \Entity\Settings;

class SettingsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }
    
    public function indexAction()
    {
        $form = new \App\Form($this->current_module_config->forms->settings->form);

        $existing_settings = Settings::fetchArray(FALSE);
        $form->setDefaults($existing_settings);
        
        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            Settings::setSettings($data);

            $this->alert('Settings updated!', 'green');
            return $this->redirectHere();
        }

        return $this->renderForm($form, 'edit', 'Site Settings');
    }
}