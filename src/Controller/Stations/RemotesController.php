<?php
namespace App\Controller\Stations;

use App\Form\EntityForm;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class RemotesController
{
    /** @var EntityManager */
    protected $em;

    /** @var EntityForm */
    protected $form;

    /** @var string */
    protected $csrf_namespace = 'stations_remotes';

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

        return $request->getView()->renderToResponse($response, 'stations/remotes/index', [
            'remotes' => $station->getRemotes(),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        $station = $request->getStation();
        $this->form->setStation($station);

        /** @var \Azura\Doctrine\Repository $remote_repo */
        $remote_repo = $this->em->getRepository(Entity\StationRemote::class);

        $record = (null !== $id)
            ? $record = $remote_repo->findOneBy(['id' => $id, 'station_id' => $station_id])
            : null;

        if (false !== $this->form->process($request, $record)) {
            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Remote Relay')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/remotes/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Remote Relay'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
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

        $this->em->flush();
        $this->em->refresh($station);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Remote Relay')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
    }
}
