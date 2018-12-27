<?php
namespace App\Controller\Stations;

use App\Radio\Frontend\AbstractFrontend;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class MountsController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_mounts';

    /** @var array */
    protected $mount_form_configs;

    /**
     * @param EntityManager $em
     * @param array $mount_form_configs
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em, array $mount_form_configs)
    {
        $this->em = $em;
        $this->mount_form_configs = $mount_form_configs;
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $frontend = $request->getStationFrontend();

        if (!$frontend::supportsMounts()) {
            throw new \Azura\Exception(__('This feature is not currently supported on this station.'));
        }

        return $request->getView()->renderToResponse($response, 'stations/mounts/index', [
            'frontend_type' => $station->getFrontendType(),
            'mounts' => $station->getMounts(),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        $station = $request->getStation();

        /** @var Entity\Repository\StationMountRepository $mount_repo */
        $mount_repo = $this->em->getRepository(Entity\StationMount::class);

        $form_config = $this->mount_form_configs[$station->getFrontendType()];
        $form = new \AzuraForms\Form($form_config);

        if (!empty($id)) {
            $record = $mount_repo->findOneBy([
                'id' => $id,
                'station_id' => $station_id,
            ]);
            $form->populate($mount_repo->toArray($record));
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
                $this->em->createQuery('UPDATE '.Entity\StationMount::class.' sm SET sm.is_default = 0
                    WHERE sm.station_id = :station_id AND sm.id != :new_default_id')
                    ->setParameter('station_id', $station->getId())
                    ->setParameter('new_default_id', $record->getId())
                    ->execute();
            }

            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Mount Point')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Mount Point'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

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

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Mount Point')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
    }
}
