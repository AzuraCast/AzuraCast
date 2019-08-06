<?php
namespace App\Controller\Stations;

use App\Entity\Station;
use App\Entity\StationRemote;
use App\Form\EntityFormManager;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/remotes/index', [
            'remotes' => $station->getRemotes(),
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            RequestHelper::getSession($request)->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Remote Relay')) . '</b>', 'green');
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:remotes:index'));
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/remotes/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Remote Relay'))
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Remote Relay')) . '</b>', 'green');

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:remotes:index'));
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
