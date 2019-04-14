<?php
namespace App\Controller\Stations;

use App\Form\EntityForm;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class RemotesController extends AbstractStationCrudController
{
    /**
     * @param EntityForm $form
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityForm $form)
    {
        parent::__construct($form);

        $this->csrf_namespace = 'stations_remotes';
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
        if (false !== $this->_doEdit($request, $id)) {
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
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Remote Relay')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:remotes:index'));
    }
}
