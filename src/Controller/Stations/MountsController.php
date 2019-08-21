<?php
namespace App\Controller\Stations;

use App\Form\StationMountForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

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

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $frontend = $request->getStationFrontend();

        if (!$frontend::supportsMounts()) {
            throw new \App\Exception\StationUnsupported(__('This feature is not currently supported on this station.'));
        }

        return $request->getView()->renderToResponse($response, 'stations/mounts/index', [
            'frontend_type' => $station->getFrontendType(),
            'mounts' => $station->getMounts(),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash('<b>' . __('Changes saved.') . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/mounts/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => $id ? __('Edit Mount Point') : __('Add Mount Point')
        ]);
    }

    public function deleteAction(ServerRequest $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('Mount Point deleted.') . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
    }
}
