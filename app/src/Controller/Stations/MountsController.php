<?php
namespace Controller\Stations;

use App\Flash;
use App\Mvc\View;
use AzuraCast\Radio\Frontend\FrontendAbstract;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class MountsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var array */
    protected $mount_form_configs;

    /**
     * MountsController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     * @param array $mount_form_configs
     */
    public function __construct(EntityManager $em, Flash $flash, array $mount_form_configs)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->mount_form_configs = $mount_form_configs;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var FrontendAbstract $frontend */
        $frontend = $request->getAttribute('station_frontend');

        if (!$frontend->supportsMounts()) {
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        }

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/mounts/index', [
            'frontend_type' => $station->getFrontendType(),
            'mounts' => $station->getMounts(),
        ]);
    }

    public function migrateAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        if ($station->getFrontendType() === 'remote') {

            $settings = (array)$station->getFrontendConfig();

            $mount = new \Entity\StationMount($station);
            $mount->setRemoteType($settings['remote_type']);
            $mount->setRemoteUrl($settings['remote_url']);
            $mount->setRemoteMount($settings['remote_mount']);
            $mount->setEnableAutodj(false);
            $mount->setIsDefault(true);

            $this->em->persist($mount);
            $this->em->flush();
        }

        return $response->redirectToRoute('stations:mounts:index', ['station' => $station_id]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var Entity\Repository\StationMountRepository $mount_repo */
        $mount_repo = $this->em->getRepository(Entity\StationMount::class);

        $form_config = $this->mount_form_configs[$station->getFrontendType()];
        $form = new \App\Form($form_config);

        if (!empty($id)) {
            $record = $mount_repo->findOneBy([
                'id' => $id,
                'station_id' => $station_id,
            ]);
            $form->setDefaults($mount_repo->toArray($record));
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationMount)) {
                $record = new Entity\StationMount($station);
            }

            $mount_repo->fromArray($record, $data);

            $this->em->persist($record);

            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            if ($uow->isEntityScheduled($record)) {
                $station->setNeedsRestart(true);
                $this->em->persist($station);
            }

            $this->em->flush();

            // Unset all other records as default if this one is set.
            if ($record->getIsDefault()) {
                $this->em->createQuery('UPDATE Entity\StationMount sm SET sm.is_default = 0
                    WHERE sm.station_id = :station_id AND sm.id != :new_default_id')
                    ->setParameter('station_id', $this->station->getId())
                    ->setParameter('new_default_id', $record->getId())
                    ->execute();
            }

            $this->em->refresh($station);

            $this->flash->alert('<b>' . _('Record updated.') . '</b>', 'green');

            return $response->redirectToRoute('stations:mounts:index', ['station' => $station_id]);
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => ($id) ? _('Edit Record') : _('Add Record')
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $record = $this->em->getRepository(Entity\StationMount::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationMount) {
            $this->em->remove($record);
        }

        $station->setNeedsRestart(true);
        $this->em->persist($station);
        $this->em->flush();

        $this->em->refresh($station);

        $this->flash->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $response->redirectToRoute('stations:mounts:index', ['station' => $station_id]);
    }
}
