<?php
namespace Controller\Stations;
use App\Http\Request;
use App\Http\Response;

class AutomationController extends \AzuraCast\Legacy\Controller
{
    protected function permissions()
    {
        return $this->acl->isAllowed('manage station automation', $this->station->getId());
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $automation_settings = (array)$this->station->getAutomationSettings();

        $form = new \App\Form($this->config->forms->automation);
        $form->setDefaults($automation_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $this->station->setAutomationSettings($data);

            $this->em->persist($this->station);
            $this->em->flush();

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectHere();
        }

        $this->view->form = $form;
    }

    public function runAction(Request $request, Response $response): Response
    {
        try {
            $automation = new \AzuraCast\Sync\RadioAutomation($this->di);

            if ($automation->runStation($this->station, true)) {
                $this->alert('<b>' . _('Automated assignment complete!') . '</b>', 'green');
            }
        } catch (\Exception $e) {
            $this->alert('<b>' . _('Automated assignment error') . ':</b><br>' . $e->getMessage(), 'red');
        }

        return $this->redirectFromHere(['action' => 'index']);
    }
}