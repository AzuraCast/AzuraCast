<?php

namespace App\Controller\Stations;

use App\Config;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use App\Settings;
use App\Sync\Task\RadioAutomation;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;

class AutomationController
{
    protected EntityManagerInterface $em;

    protected RadioAutomation $sync_task;

    protected Settings $app_settings;

    protected array $form_config;

    public function __construct(
        EntityManagerInterface $em,
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

            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);

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
                $request->getFlash()->addMessage('<b>' . __('Automated assignment complete!') . '</b>', Flash::SUCCESS);
            }
        } catch (Exception $e) {
            $request->getFlash()->addMessage(
                '<b>' . __('Automated assignment error') . ':</b><br>' . $e->getMessage(),
                Flash::ERROR
            );
        }

        return $response->withRedirect($request->getRouter()->fromHere('stations:automation:index'));
    }
}
