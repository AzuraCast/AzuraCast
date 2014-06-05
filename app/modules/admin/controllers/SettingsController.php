<?php
use \Entity\Settings;

class Admin_SettingsController extends \DF\Controller\Action
{
    public function permissions()
    {
        return $this->acl->isAllowed('admin_all');
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
            $this->redirectHere();
        }
        
        $this->view->form = $form;
    }
}