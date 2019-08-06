<?php
namespace App\Controller\Stations;

use App\Form\Form;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Sync\Task\RadioAutomation;
use Azura\Config;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        $automation_settings = (array)$station->getAutomationSettings();

        $form = new Form($this->form_config);
        $form->populate($automation_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $station->setAutomationSettings($data);

            $this->em->persist($station);
            $this->em->flush();

            RequestHelper::getSession($request)->flash(__('Changes saved.'), 'green');

            return ResponseHelper::withRedirect($response, $request->getUri());
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/automation/index', [
            'app_settings' => $this->app_settings,
            'form' => $form,
        ]);
    }

    public function runAction(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        try {
            if ($this->sync_task->runStation($station, true)) {
                RequestHelper::getSession($request)->flash('<b>' . __('Automated assignment complete!') . '</b>', 'green');
            }
        } catch (\Exception $e) {
            RequestHelper::getSession($request)->flash('<b>' . __('Automated assignment error') . ':</b><br>' . $e->getMessage(), 'red');
        }

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:automation:index'));
    }
}
