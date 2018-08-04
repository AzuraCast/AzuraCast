<?php
namespace App\Controller\Stations;

use App\Sync\Task\RadioAutomation;
use Doctrine\ORM\EntityManager;
use App\Http\Request;
use App\Http\Response;

class AutomationController
{
    /** @var EntityManager */
    protected $em;

    /** @var RadioAutomation */
    protected $sync_task;

    /** @var array */
    protected $form_config;

    /**
     * AutomationController constructor.
     * @param EntityManager $em
     * @param array $form_config
     * @param RadioAutomation $sync_task
     */
    public function __construct(EntityManager $em, RadioAutomation $sync_task, array $form_config)
    {
        $this->em = $em;
        $this->sync_task = $sync_task;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $station = $request->getStation();

        $automation_settings = (array)$station->getAutomationSettings();

        $form = new \AzuraForms\Form($this->form_config);
        $form->populate($automation_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $station->setAutomationSettings($data);

            $this->em->persist($station);
            $this->em->flush();

            $request->getSession()->flash(__('Changes saved.'), 'green');

            return $response->redirectHere();
        }

        return $request->getView()->renderToResponse($response, 'stations/automation/index', [
            'form' => $form,
        ]);
    }

    public function runAction(Request $request, Response $response, $station_id): Response
    {
        $station = $request->getStation();

        try {
            if ($this->sync_task->runStation($station, true)) {
                $request->getSession()->flash('<b>' . __('Automated assignment complete!') . '</b>', 'green');
            }
        } catch (\Exception $e) {
            $request->getSession()->flash('<b>' . __('Automated assignment error') . ':</b><br>' . $e->getMessage(), 'red');
        }

        return $response->redirectToRoute('stations:automation:index', ['station' => $station_id]);
    }
}
