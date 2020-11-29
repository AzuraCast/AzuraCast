<?php

namespace App\Controller\Stations;

use App\Entity;
use App\Form\StationWebhookForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use App\Webhook\Dispatcher;
use Psr\Http\Message\ResponseInterface;

class WebhooksController extends AbstractStationCrudController
{
    protected Dispatcher $dispatcher;

    protected array $webhook_config;

    public function __construct(
        StationWebhookForm $form,
        Dispatcher $dispatcher
    ) {
        parent::__construct($form);

        $this->webhook_config = $form->getConfig();

        $this->csrf_namespace = 'stations_webhooks';
        $this->dispatcher = $dispatcher;
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/webhooks/index', [
            'webhooks' => $station->getWebhooks(),
            'webhook_config' => $this->webhook_config,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function addAction(ServerRequest $request, Response $response, $type = null): ResponseInterface
    {
        $view = $request->getView();
        if ($type === null) {
            return $view->renderToResponse($response, 'stations/webhooks/add', [
                'connectors' => array_filter($this->webhook_config['webhooks'], function ($webhook) {
                    return !empty($webhook['name']);
                }),
            ]);
        }

        $record = new Entity\StationWebhook($request->getStation(), $type);

        if (false !== $this->form->process($request, $record)) {
            $request->getFlash()->addMessage('<b>' . __('Web Hook added.') . '</b>', Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
        }

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Add Web Hook'),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage('<b>' . __('Web Hook updated.') . '</b>', Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Edit Web Hook'),
        ]);
    }

    public function toggleAction(
        ServerRequest $request,
        Response $response,
        $id,
        $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        /** @var Entity\StationWebhook $record */
        $record = $this->getRecord($request->getStation(), $id);

        $new_status = $record->toggleEnabled();

        $this->em->persist($record);
        $this->em->flush();

        $request->getFlash()->addMessage(
            '<b>' . ($new_status ? __('Web hook enabled.') : __('Web Hook disabled.')) . '</b>',
            Flash::SUCCESS
        );
        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }

    public function testAction(
        ServerRequest $request,
        Response $response,
        $id,
        $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $station = $request->getStation();

        /** @var Entity\StationWebhook $record */
        $record = $this->getRecord($station, $id);

        $handler_response = $this->dispatcher->testDispatch($station, $record);
        $log_records = $handler_response->getRecords();

        return $request->getView()->renderToResponse($response, 'system/log_view', [
            'title' => __('Web Hook Test Output'),
            'log_records' => $log_records,
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $id,
        $csrf
    ): ResponseInterface {
        $this->doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('Web Hook deleted.') . '</b>', Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }
}
