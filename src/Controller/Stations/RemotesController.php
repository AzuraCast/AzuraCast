<?php
namespace App\Controller\Stations;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class RemotesController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_remotes';

    /** @var array */
    protected $form_config;

    /**
     * @param EntityManager $em
     * @param array $form_config
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em, array $form_config)
    {
        $this->em = $em;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/remotes/index', [
            'remotes' => $station->getRemotes(),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): Response
    {
        $station = $request->getStation();

        /** @var Entity\Repository\BaseRepository $remote_repo */
        $remote_repo = $this->em->getRepository(Entity\StationRemote::class);

        $form = new \AzuraForms\Form($this->form_config);

        if (!empty($id)) {
            $record = $remote_repo->findOneBy([
                'id' => $id,
                'station_id' => $station_id,
            ]);
            $form->populate($remote_repo->toArray($record));
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationRemote)) {
                $record = new Entity\StationRemote($station);
            }

            $remote_repo->fromArray($record, $data);

            $this->em->persist($record);

            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            if ($uow->isEntityScheduled($record)) {
                $station->setNeedsRestart(true);
                $this->em->persist($station);
            }

            $this->em->flush();
            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Remote Relay')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Remote Relay'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

        $record = $this->em->getRepository(Entity\StationRemote::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationRemote) {
            $this->em->remove($record);
        }

        $station->setNeedsRestart(true);
        $this->em->persist($station);
        $this->em->flush();

        $this->em->refresh($station);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Remote Relay')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
    }
}
