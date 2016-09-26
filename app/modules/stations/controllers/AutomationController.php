<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\StationMedia;
use Entity\StationPlaylist;

class AutomationController extends BaseController
{
    public function indexAction()
    {
        $automation_settings = (array)$this->station->automation_settings;

        $form = new \App\Form($this->current_module_config->forms->automation);
        $form->setDefaults($automation_settings);

        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $this->station->automation_settings = $data;
            $this->station->save();

            $this->alert('Changes saved!', 'green');
            return $this->redirectHere();
        }

        $this->view->form = $form;
    }

    public function runAction()
    {
        try
        {
            $automation = new \App\Sync\RadioAutomation($this->di);

            if ($automation->runStation($this->station, true))
                $this->alert('<b>Automated assignment complete!</b>', 'green');
        }
        catch(\Exception $e)
        {
            $this->alert('<b>Automated assignment error:</b><br>'.$e->getMessage(), 'red');
        }

        return $this->redirectFromHere(['action' => 'index']);
    }
}