<?php
namespace Controller\Stations;

use App\Mvc\View;
use AzuraCast\Sync\Task\RadioAutomation;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Flash;
use App\Http\Request;
use App\Http\Response;

class AutomationController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var RadioAutomation */
    protected $sync_task;

    /** @var array */
    protected $form_config;

    /**
     * AutomationController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     * @param array $form_config
     * @param RadioAutomation $sync_task
     */
    public function __construct(EntityManager $em, Flash $flash, RadioAutomation $sync_task, array $form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->sync_task = $sync_task;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $automation_settings = (array)$station->getAutomationSettings();

        $form = new \AzuraForms\Form($this->form_config);
        $form->populate($automation_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $station->setAutomationSettings($data);

            $this->em->persist($station);
            $this->em->flush();

            $this->flash->alert(__('Changes saved.'), 'green');

            return $response->redirectHere();
        }

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/automation/index', [
            'form' => $form,
        ]);
    }

    public function runAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        try {
            if ($this->sync_task->runStation($station, true)) {
                $this->flash->alert('<b>' . __('Automated assignment complete!') . '</b>', 'green');
            }
        } catch (\Exception $e) {
            $this->flash->alert('<b>' . __('Automated assignment error') . ':</b><br>' . $e->getMessage(), 'red');
        }

        return $response->redirectToRoute('stations:automation:index', ['station' => $station_id]);
    }
}