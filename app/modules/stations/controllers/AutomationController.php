<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\StationMedia;
use Entity\StationPlaylist;

class AutomationController extends BaseController
{
    protected function permissions()
    {
        return $this->acl->isAllowed('manage station automation', $this->station->id);
    }

    public function indexAction()
    {
        $automation_settings = (array)$this->station->automation_settings;

        $form = new \App\Form($this->current_module_config->forms->automation);
        $form->setDefaults($automation_settings);

        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $this->station->automation_settings = $data;

            $this->em->persist($this->station);
            $this->em->flush();

            $this->alert(_('Changes saved.'), 'green');
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
                $this->alert('<b>'._('Automated assignment complete!').'</b>', 'green');
        }
        catch(\Exception $e)
        {
            $this->alert('<b>'._('Automated assignment error').':</b><br>'.$e->getMessage(), 'red');
        }

        return $this->redirectFromHere(['action' => 'index']);
    }
}