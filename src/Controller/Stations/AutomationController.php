<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Config;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use App\Sync\Task\RunAutomatedAssignmentTask;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;

class AutomationController
{
    protected array $form_config;

    public function __construct(
        protected EntityManagerInterface $em,
        protected RunAutomatedAssignmentTask $sync_task,
        Config $config
    ) {
        $this->form_config = $config->get('forms/automation');
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $automation_settings = (array)$station->getAutomationSettings();

        $form = new Form($this->form_config);
        $form->populate($automation_settings);

        if ($form->isValid($request)) {
            $data = $form->getValues();

            $station->setAutomationSettings($data);

            $this->em->persist($station);
            $this->em->flush();

            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);

            return $response->withRedirect((string)$request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'stations/automation/index', [
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

        return $response->withRedirect((string)$request->getRouter()->fromHere('stations:automation:index'));
    }
}
