<?php
namespace App\Controller\Stations;

use App\Provider\StationsProvider;
use App\Webhook\Dispatcher;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

/**
 * Class WebhooksController
 * @package App\Controller\Stations
 * @see StationsProvider
 */
class WebhooksController
{
    /** @var EntityManager */
    protected $em;

    /** @var Dispatcher */
    protected $dispatcher;

    /** @var string */
    protected $csrf_namespace = 'stations_webhooks';

    /** @var array */
    protected $webhook_config;

    /** @var array */
    protected $webhook_forms;

    /**
     * @param EntityManager $em
     * @param Dispatcher $dispatcher
     * @param array $webhook_config
     * @param array $webhook_forms
     * @see \App\Provider\StationsProvider
     */
    public function __construct(
        EntityManager $em,
        Dispatcher $dispatcher,
        array $webhook_config,
        array $webhook_forms
    )
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->webhook_config = $webhook_config;
        $this->webhook_forms = $webhook_forms;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/webhooks/index', [
            'webhooks' => $station->getWebhooks(),
            'webhook_config' => $this->webhook_config,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function addAction(Request $request, Response $response, $station_id, $type = null): Response
    {
        $station = $request->getStation();

        $view = $request->getView();

        if ($type === null) {
            return $view->renderToResponse($response, 'stations/webhooks/add', [
                'connectors' => array_filter($this->webhook_config['webhooks'], function($webhook) {
                    return !empty($webhook['name']);
                }),
            ]);
        }

        if (!isset($this->webhook_forms[$type])) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Web Hook')));
        }

        $form_config = $this->webhook_forms[$type];
        $form = new \AzuraForms\Form($form_config);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            /** @var Entity\Repository\BaseRepository $record_repo */
            $record_repo = $this->em->getRepository(Entity\StationWebhook::class);

            $record = new Entity\StationWebhook($station, $type);

            $record_repo->fromArray($record, $data);
            $this->em->persist($record);
            $this->em->flush();

            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . __('%s added.', __('Web Hook')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
        }

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Add %s', __('Web Hook'))
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id): Response
    {
        $station = $request->getStation();

        /** @var Entity\Repository\BaseRepository $record_repo */
        $record_repo = $this->em->getRepository(Entity\StationWebhook::class);

        $record = $record_repo->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationWebhook)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Web Hook')));
        }

        $webhook_type = $record->getType();
        $form_config = $this->webhook_forms[$webhook_type];

        $form = new \AzuraForms\Form($form_config);
        $form->populate($record_repo->toArray($record));

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $record_repo->fromArray($record, $data);
            $this->em->persist($record);
            $this->em->flush();

            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . __('%s updated.', __('Web Hook')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Edit %s', __('Web Hook'))
        ]);
    }

    public function toggleAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

        $record = $this->em->getRepository(Entity\StationWebhook::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationWebhook)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Web Hook')));
        }

        $new_status = $record->toggleEnabled();
        $this->em->flush();
        $this->em->refresh($station);

        $request->getSession()->flash('<b>' . sprintf(($new_status) ? __('%s enabled.') : __('%s disabled.'), __('Web Hook')) . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }

    public function testAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

        $record = $this->em->getRepository(Entity\StationWebhook::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationWebhook)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Web Hook')));
        }

        $handler_response = $this->dispatcher->testDispatch($station, $record);

        $log_records = $handler_response->getRecords();

        return $request->getView()->renderToResponse($response, 'system/log_view', [
            'title' => __('Web Hook Test Output'),
            'log_records' => $log_records,
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

        $record = $this->em->getRepository(Entity\StationWebhook::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationWebhook) {
            $this->em->remove($record);
            $this->em->flush();
            $this->em->refresh($station);
        }

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Web Hook')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:webhooks:index'));
    }
}
