<?php
namespace App\Controller\Stations;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class WebhooksController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_webhooks';

    /** @var array */
    protected $form_configs;

    /**
     * MountsController constructor.
     * @param EntityManager $em
     * @param array $mount_form_configs
     */
    public function __construct(EntityManager $em, array $form_configs)
    {
        $this->em = $em;
        $this->form_configs = $form_configs;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/webhooks/index', [
            'webhooks' => $station->getWebhooks(),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function addAction(Request $request, Response $response, $station_id, $type = null): Response
    {
        $station = $request->getStation();

        $view = $request->getView();

        if ($type === null) {
            return $view->renderToResponse($response, 'stations/webhooks/add', [
                'connectors' => \App\Webhook\Dispatcher::getConnectors(),
            ]);
        }

        if (!isset($this->form_configs[$type])) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Web Hook')));
        }

        $form_config = $this->form_configs[$type];
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

            return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
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
        $form_config = $this->form_configs[$webhook_type];

        $form = new \AzuraForms\Form($form_config);
        $form->populate($record_repo->toArray($record));

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            $record_repo->fromArray($record, $data);
            $this->em->persist($record);
            $this->em->flush();

            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . __('%s updated.', __('Web Hook')) . '</b>', 'green');

            return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
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
        return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
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

        return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
    }
}
