<?php
namespace App\Controller\Stations;

use App\Entity;
use App\Form\EntityForm;
use App\Form\StationWebhookForm;
use App\Provider\StationsProvider;
use App\Webhook\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class WebhooksController
 * @package App\Controller\Stations
 * @see StationsProvider
 */
class WebhooksController extends AbstractStationCrudController
{
    /** @var Dispatcher */
    protected $dispatcher;

    /** @var array */
    protected $webhook_config;

    /**
     * @param EntityForm $form
     * @param Dispatcher $dispatcher
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(
        EntityForm $form,
        Dispatcher $dispatcher
    ) {
        parent::__construct($form);

        if ($form instanceof StationWebhookForm) {
            $this->webhook_config = $form->getConfig();
        }

        $this->csrf_namespace = 'stations_webhooks';
        $this->dispatcher = $dispatcher;
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $station = \App\Http\RequestHelper::getStation($request);

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'stations/webhooks/index', [
            'webhooks' => $station->getWebhooks(),
            'webhook_config' => $this->webhook_config,
            'csrf' => \App\Http\RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function addAction(Request $request, Response $response, $station_id, $type = null): ResponseInterface
    {
        $view = \App\Http\RequestHelper::getView($request);
        if ($type === null) {
            return $view->renderToResponse($response, 'stations/webhooks/add', [
                'connectors' => array_filter($this->webhook_config['webhooks'], function($webhook) {
                    return !empty($webhook['name']);
                }),
            ]);
        }

        $record = new Entity\StationWebhook(\App\Http\RequestHelper::getStation($request), $type);

        if (false !== $this->form->process($request, $record)) {
            \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('%s added.', __('Web Hook')) . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
        }

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Add %s', __('Web Hook'))
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('%s updated.', __('Web Hook')) . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Edit %s', __('Web Hook'))
        ]);
    }

    public function toggleAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        \App\Http\RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\StationWebhook $record */
        $record = $this->_getRecord(\App\Http\RequestHelper::getStation($request), $id);

        $new_status = $record->toggleEnabled();

        $this->em->persist($record);
        $this->em->flush();

        \App\Http\RequestHelper::getSession($request)->flash('<b>' . sprintf(($new_status) ? __('%s enabled.') : __('%s disabled.'), __('Web Hook')) . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }

    public function testAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        \App\Http\RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = \App\Http\RequestHelper::getStation($request);

        /** @var Entity\StationWebhook $record */
        $record = $this->_getRecord($station, $id);

        $handler_response = $this->dispatcher->testDispatch($station, $record);
        $log_records = $handler_response->getRecords();

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'system/log_view', [
            'title' => __('Web Hook Test Output'),
            'log_records' => $log_records,
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Web Hook')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }
}
