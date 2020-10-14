<?php

namespace App\Controller\Stations;

use App\Entity\Station;
use App\Entity\StationRemote;
use App\Exception\PermissionDeniedException;
use App\Form\StationRemoteForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class RemotesController extends AbstractStationCrudController
{
    public function __construct(StationRemoteForm $form)
    {
        parent::__construct($form);
        $this->csrf_namespace = 'stations_remotes';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/remotes/index', [
            'remotes' => $station->getRemotes(),
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage(
                '<b>' . ($id ? __('Remote Relay updated.') : __('Remote Relay added.')) . '</b>',
                Flash::SUCCESS
            );
            return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/remotes/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => $id ? __('Edit Remote Relay') : __('Add Remote Relay'),
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $id,
        $csrf
    ): ResponseInterface {
        $this->doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('Remote Relay deleted.') . '</b>', Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
    }

    protected function getRecord(Station $station, $id = null): ?object
    {
        $record = parent::getRecord($station, $id);

        if ($record instanceof StationRemote && !$record->isEditable()) {
            throw new PermissionDeniedException(__('This record cannot be edited.'));
        }

        return $record;
    }
}
