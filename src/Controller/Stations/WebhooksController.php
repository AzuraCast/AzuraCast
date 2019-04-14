<?php
namespace App\Controller\Stations;

use App\Form\EntityForm;
use App\Form\StationWebhookForm;
use App\Provider\StationsProvider;
use App\Webhook\Dispatcher;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

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

        /** @var StationWebhookForm $form */
        $this->webhook_config = $form->getConfig();

        $this->csrf_namespace = 'stations_webhooks';
        $this->dispatcher = $dispatcher;
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/webhooks/index', [
            'webhooks' => $station->getWebhooks(),
            'webhook_config' => $this->webhook_config,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function addAction(Request $request, Response $response, $station_id, $type = null): ResponseInterface
    {
        $view = $request->getView();
        if ($type === null) {
            return $view->renderToResponse($response, 'stations/webhooks/add', [
                'connectors' => array_filter($this->webhook_config['webhooks'], function($webhook) {
                    return !empty($webhook['name']);
                }),
            ]);
        }

        $record = new Entity\StationWebhook($request->getStation(), $type);

        if (false !== $this->form->process($request, $record)) {
            $request->getSession()->flash('<b>' . __('%s added.', __('Web Hook')) . '</b>', 'green');
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
            $request->getSession()->flash('<b>' . __('%s updated.', __('Web Hook')) . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Edit %s', __('Web Hook'))
        ]);
    }

    public function toggleAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\StationWebhook $record */
        $record = $this->_getRecord($request->getStation(), $id);

        $new_status = $record->toggleEnabled();

        $this->em->persist($record);
        $this->em->flush();

        $request->getSession()->flash('<b>' . sprintf(($new_status) ? __('%s enabled.') : __('%s disabled.'), __('Web Hook')) . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }

    public function testAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

        /** @var Entity\StationWebhook $record */
        $record = $this->_getRecord($station, $id);

        $handler_response = $this->dispatcher->testDispatch($station, $record);
        $log_records = $handler_response->getRecords();

        return $request->getView()->renderToResponse($response, 'system/log_view', [
            'title' => __('Web Hook Test Output'),
            'log_records' => $log_records,
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Web Hook')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }
}
