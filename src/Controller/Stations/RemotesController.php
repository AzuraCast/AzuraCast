<?php
namespace App\Controller\Stations;

use App\Entity\Station;
use App\Entity\StationRemote;
use App\Form\EntityFormManager;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RemotesController extends AbstractStationCrudController
{
    /**
     * @param EntityFormManager $formManager
     * @param Config $config
     */
    public function __construct(EntityFormManager $formManager, Config $config)
    {
        $form = $formManager->getForm(StationRemote::class, $config->get('forms/remote'));
        parent::__construct($form);

        $this->csrf_namespace = 'stations_remotes';
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $station = \App\Http\RequestHelper::getStation($request);

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'stations/remotes/index', [
            'remotes' => $station->getRemotes(),
            'csrf' => \App\Http\RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            \App\Http\RequestHelper::getSession($request)->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Remote Relay')) . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'stations/remotes/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Remote Relay'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Remote Relay')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
    }

    protected function _getRecord(Station $station, $id = null): ?object
    {
        $record = parent::_getRecord($station, $id);

        if ($record instanceof StationRemote && !$record->isEditable()) {
            throw new \App\Exception\PermissionDenied('This record cannot be edited.');
        }

        return $record;
    }
}
