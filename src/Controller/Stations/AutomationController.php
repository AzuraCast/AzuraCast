<?php
namespace App\Controller\Stations;

use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Settings;
use App\Sync\Task\RadioAutomation;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Http\Message\ResponseInterface;

class AutomationController
{
    /** @var EntityManager */
    protected $em;

    /** @var RadioAutomation */
    protected $sync_task;

    /** @var Settings */
    protected $app_settings;

    /** @var array */
    protected $form_config;

    /**
     * @param EntityManager $em
     * @param RadioAutomation $sync_task
     * @param Settings $app_settings
     * @param Config $config
     */
    public function __construct(
        EntityManager $em,
        RadioAutomation $sync_task,
        Settings $app_settings,
        Config $config
    ) {
        $this->em = $em;
        $this->sync_task = $sync_task;
        $this->app_settings = $app_settings;
        $this->form_config = $config->get('forms/automation');
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $automation_settings = (array)$station->getAutomationSettings();

        $form = new Form($this->form_config);
        $form->populate($automation_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $station->setAutomationSettings($data);

            $this->em->persist($station);
            $this->em->flush();

            $request->getSession()->flash(__('Changes saved.'), 'green');

            return $response->withRedirect($request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'stations/automation/index', [
            'app_settings' => $this->app_settings,
            'form' => $form,
        ]);
    }

    public function runAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        try {
            if ($this->sync_task->runStation($station, true)) {
                $request->getSession()->flash('<b>' . __('Automated assignment complete!') . '</b>', 'green');
            }
        } catch (Exception $e) {
            $request->getSession()->flash('<b>' . __('Automated assignment error') . ':</b><br>' . $e->getMessage(),
                'red');
        }

        return $response->withRedirect($request->getRouter()->fromHere('stations:automation:index'));
    }
}
