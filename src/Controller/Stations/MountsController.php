<?php
namespace App\Controller\Stations;

use App\Form\EntityForm;
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

    /** @var EntityForm */
    protected $form;

    /**
     * @param EntityForm $form
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityForm $form)
    {
        $this->form = $form;
        $this->em = $form->getEntityManager();
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
        $this->form->setStation($station);

        /** @var Entity\Repository\StationMountRepository $mount_repo */
        $mount_repo = $this->em->getRepository(Entity\StationMount::class);

        $record = (null !== $id)
            ? $mount_repo->findOneBy(['id' => $id, 'station_id' => $station_id])
            : null;

        if (false !== $this->form->process($request, $record)) {
            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Mount Point')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/mounts/edit', [
            'form' => $this->form,
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

        $this->em->flush();

        $this->em->refresh($station);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Mount Point')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
    }
}
