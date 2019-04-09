<?php
namespace App\Controller\Admin;

use App\Form;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class StationsController
{
    /** @var Entity\Repository\StationRepository */
    protected $record_repo;

    /** @var Form\StationForm */
    protected $station_form;

    /** @var Form\StationCloneForm */
    protected $clone_form;

    /** @var string */
    protected $csrf_namespace = 'admin_stations';

    /**
     * @param Form\StationForm $station_form
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(
        Form\StationForm $station_form,
        Form\StationCloneForm $clone_form
    ) {
        $this->station_form = $station_form;
        $this->clone_form = $clone_form;

        $this->record_repo = $station_form->getEntityRepository();
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $stations = $this->record_repo->fetchArray(false, 'name');

        return $request->getView()->renderToResponse($response, 'admin/stations/index', [
            'stations' => $stations,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): ResponseInterface
    {
        $record = (!empty($id))
            ? $this->record_repo->find((int)$id)
            : null;

        if (false !== $this->station_form->process($request, $record)) {
            $request->getSession()->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Station')), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse($response, 'admin/stations/edit', [
            'form' => $this->station_form,
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Station')),
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $record = $this->record_repo->find((int)$id);

        if ($record instanceof Entity\Station) {
            $this->record_repo->destroy($record);
        }

        $request->getSession()->flash(__('%s deleted.', __('Station')), 'green');
        return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
    }

    public function cloneAction(Request $request, Response $response, $id): ResponseInterface
    {
        $record = $this->record_repo->find((int)$id);
        if (!($record instanceof Entity\Station)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Station')));
        }

        if (false !== $this->clone_form->process($request, $record)) {
            $request->getSession()->flash(__('Changes saved.'), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->clone_form,
            'render_mode' => 'edit',
            'title' => __('Clone Station: %s', $record->getName())
        ]);
    }
}
