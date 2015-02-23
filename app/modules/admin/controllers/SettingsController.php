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
        $form = new \DF\Form($this->current_module_config->forms->settings->form);

        $existing_settings = Settings::fetchArray(FALSE);
        $form->setDefaults($existing_settings);
        
        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();
            
            foreach($data as $key => $value)
            {
                Settings::setSetting($key, $value);
            }           
            
            $this->alert('Settings updated!');
            return $this->redirectHere();
        }

        $this->renderForm($form, 'edit', 'Site Settings');
    }
}