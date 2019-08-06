<?php
namespace App\Controller\Stations;

use App\Form\EntityForm;
use App\Form\StationMountForm;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MountsController extends AbstractStationCrudController
{
    /**
     * @param StationMountForm $form
     */
    public function __construct(StationMountForm $form)
    {
        parent::__construct($form);

        $this->csrf_namespace = 'stations_mounts';
    }

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $frontend = RequestHelper::getStationFrontend($request);

        if (!$frontend::supportsMounts()) {
            throw new \App\Exception\StationUnsupported(__('This feature is not currently supported on this station.'));
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/mounts/index', [
            'frontend_type' => $station->getFrontendType(),
            'mounts' => $station->getMounts(),
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            RequestHelper::getSession($request)->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Mount Point')) . '</b>', 'green');
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:mounts:index'));
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/mounts/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Mount Point'))
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Mount Point')) . '</b>', 'green');
        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:mounts:index'));
    }
}
