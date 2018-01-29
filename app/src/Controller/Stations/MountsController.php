<?php
namespace Controller\Stations;

use Entity;
use Entity\StationMount;
use App\Http\Request;
use App\Http\Response;

class MountsController extends BaseController
{
    protected function preDispatch()
    {
        if (!$this->frontend->supportsMounts()) {
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        }

        return parent::preDispatch();
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('manage station mounts', $this->station->getId());
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $this->view->frontend_type = $this->station->getFrontendType();
        $this->view->mounts = $this->station->getMounts();
    }

    public function migrateAction(Request $request, Response $response): Response
    {
        if ($this->station->getFrontendType() == 'remote') {

            $settings = (array)$this->station->getFrontendConfig();

            $mount = new \Entity\StationMount($this->station);
            $mount->setRemoteType($settings['remote_type']);
            $mount->setRemoteUrl($settings['remote_url']);
            $mount->setRemoteMount($settings['remote_mount']);
            $mount->setEnableAutodj(false);
            $mount->setIsDefault(true);

            $this->em->persist($mount);
            $this->em->flush();
        }

        return $this->redirectToName('stations:mounts:index', ['station' => $this->station->getId()]);
    }

    public function editAction(Request $request, Response $response): Response
    {
        /** @var Entity\Repository\StationMountRepository $mount_repo */
        $mount_repo = $this->em->getRepository(Entity\StationMount::class);

        $form_config = $this->config->forms->{'mount_'.$this->station->getFrontendType()};
        $form = new \App\Form($form_config);

        if ($this->hasParam('id')) {
            $record = $mount_repo->findOneBy([
                'id' => $this->getParam('id'),
                'station_id' => $this->station->getId(),
            ]);
            $form->setDefaults($mount_repo->toArray($record));
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationMount)) {
                $record = new Entity\StationMount($this->station);
            }

            $mount_repo->fromArray($record, $data);

            $this->em->persist($record);

            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            if ($uow->isEntityScheduled($record)) {
                $this->station->setNeedsRestart(true);
                $this->em->persist($this->station);
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

            $this->em->refresh($this->station);

            $this->alert('<b>' . _('Record updated.') . '</b>', 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => null]);
        }

        $title = ($this->hasParam('id')) ? _('Edit Record') : _('Add Record');

        return $this->renderForm($form, 'edit', $title);
    }

    public function deleteAction(Request $request, Response $response): Response
    {
        $id = (int)$this->getParam('id');

        $record = $this->em->getRepository(Entity\StationMount::class)->findOneBy([
            'id' => $id,
            'station_id' => $this->station->getId()
        ]);

        if ($record instanceof Entity\StationMount) {
            $this->em->remove($record);
        }

        $this->station->setNeedsRestart(true);
        $this->em->persist($this->station);
        $this->em->flush();

        $this->em->refresh($this->station);

        $this->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null]);
    }
}
