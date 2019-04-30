<?php
namespace App\Controller\Admin;

use App\Form;
use App\Entity;
use App\Form\EntityForm;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class StationsController extends AbstractAdminCrudController
{
    /** @var Form\StationCloneForm */
    protected $clone_form;

    /**
     * @param EntityForm $form
     * @param EntityForm $clone_form
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(EntityForm $form, EntityForm $clone_form)
    {
        parent::__construct($form);

        $this->clone_form = $clone_form;
        $this->csrf_namespace = 'admin_stations';
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
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Station')), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse($response, 'admin/stations/edit', [
            'form' => $this->form,
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Station')),
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $record = $this->record_repo->find((int)$id);
        if ($record instanceof Entity\Station) {
            /** @var Entity\Repository\StationRepository $record_repo */
            $record_repo = $this->record_repo;
            $record_repo->destroy($record);
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
