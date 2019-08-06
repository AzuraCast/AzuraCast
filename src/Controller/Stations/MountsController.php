<?php
namespace App\Controller\Stations;

use App\Form\EntityForm;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MountsController extends AbstractStationCrudController
{
    /**
     * @param EntityForm $form
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityForm $form)
    {
        parent::__construct($form);

        $this->csrf_namespace = 'stations_mounts';
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $station = \App\Http\RequestHelper::getStation($request);
        $frontend = \App\Http\RequestHelper::getStationFrontend($request);

        if (!$frontend::supportsMounts()) {
            throw new \App\Exception\StationUnsupported(__('This feature is not currently supported on this station.'));
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'stations/mounts/index', [
            'frontend_type' => $station->getFrontendType(),
            'mounts' => $station->getMounts(),
            'csrf' => \App\Http\RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            \App\Http\RequestHelper::getSession($request)->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Mount Point')) . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'stations/mounts/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Mount Point'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Mount Point')) . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
    }
}
