<?php

namespace App\Controller\Stations;

use App\Exception\StationUnsupportedException;
use App\Form\StationMountForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class MountsController extends AbstractStationCrudController
{
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
            throw new StationUnsupportedException(__('This feature is not currently supported on this station.'));
        }

        return $request->getView()->renderToResponse($response, 'stations/mounts/index', [
            'frontend_type' => $station->getFrontendType(),
            'mounts' => $station->getMounts(),
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage('<b>' . __('Changes saved.') . '</b>', Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/mounts/edit', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => $id ? __('Edit Mount Point') : __('Add Mount Point'),
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $id,
        $csrf
    ): ResponseInterface {
        $this->doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('Mount Point deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->fromHere('stations:mounts:index'));
    }
}
