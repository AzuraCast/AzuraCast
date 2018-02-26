<?php
namespace Controller\Stations;

use App\Csrf;
use App\Flash;
use App\Mvc\View;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class WebhooksController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var Csrf */
    protected $csrf;

    /** @var string */
    protected $csrf_namespace = 'stations_webhooks';

    /** @var array */
    protected $form_configs;

    /**
     * MountsController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     * @param array $mount_form_configs
     */
    public function __construct(EntityManager $em, Flash $flash, Csrf $csrf, array $form_configs)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->form_configs = $form_configs;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/webhooks/index', [
            'webhooks' => $station->getWebhooks(),
            'csrf' => $this->csrf->generate($this->csrf_namespace),
        ]);
    }

    public function addAction(Request $request, Response $response, $station_id, $type = null): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var View $view */
        $view = $request->getAttribute('view');

        if ($type === null) {
            return $view->renderToResponse($response, 'stations/webhooks/add', [
                'connectors' => \AzuraCast\Webhook\Dispatcher::getConnectors(),
            ]);
        }

        if (!isset($this->form_configs[$type])) {
            throw new \App\Exception\NotFound(sprintf(_('%s not found.'), _('Web Hook')));
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

            $this->flash->alert('<b>' . sprintf(_('%s added.'), _('Web Hook')) . '</b>', 'green');

            return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
        }

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(_('Add %s'), _('Web Hook'))
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var Entity\Repository\BaseRepository $record_repo */
        $record_repo = $this->em->getRepository(Entity\StationWebhook::class);

        $record = $record_repo->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationWebhook)) {
            throw new \App\Exception\NotFound(sprintf(_('%s not found.'), _('Web Hook')));
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

            $this->flash->alert('<b>' . sprintf(_('%s updated.'), _('Web Hook')) . '</b>', 'green');

            return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(_('Edit %s'), _('Web Hook'))
        ]);
    }

    public function toggleAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $record = $this->em->getRepository(Entity\StationWebhook::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationWebhook)) {
            throw new \App\Exception\NotFound(sprintf(_('%s not found.'), _('Web Hook')));
        }

        $new_status = $record->toggleEnabled();
        $this->em->flush();
        $this->em->refresh($station);

        $this->flash->alert('<b>' . sprintf(($new_status) ? _('%s enabled.') : _('%s disabled.'), _('Web Hook')) . '</b>', 'green');
        return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $record = $this->em->getRepository(Entity\StationWebhook::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationWebhook) {
            $this->em->remove($record);
            $this->em->flush();
            $this->em->refresh($station);
        }

        $this->flash->alert('<b>' . sprintf(_('%s deleted.'), _('Web Hook')) . '</b>', 'green');

        return $response->redirectToRoute('stations:webhooks:index', ['station' => $station_id]);
    }
}
