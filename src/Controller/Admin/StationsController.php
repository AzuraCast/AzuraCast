<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Exception\NotFoundException;
use App\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class StationsController extends AbstractAdminCrudController
{
    /** @var Form\StationCloneForm */
    protected $clone_form;

    /**
     * @param Form\StationForm $form
     * @param Form\StationCloneForm $clone_form
     */
    public function __construct(Form\StationForm $form, Form\StationCloneForm $clone_form)
    {
        parent::__construct($form);

        $this->clone_form = $clone_form;
        $this->csrf_namespace = 'admin_stations';
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $stations = $this->record_repo->fetchArray(false, 'name');

        return $request->getView()->renderToResponse($response, 'admin/stations/index', [
            'stations' => $stations,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getFlash()->addMessage(($id ? __('Station updated.') : __('Station added.')), Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse($response, 'admin/stations/edit', [
            'form' => $this->form,
            'title' => $id ? __('Edit Station') : 'Add Station',
        ]);
    }

    public function deleteAction(ServerRequest $request, Response $response, $id, $csrf): ResponseInterface
    {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $record = $this->record_repo->find((int)$id);
        if ($record instanceof Entity\Station) {
            /** @var Entity\Repository\StationRepository $record_repo */
            $record_repo = $this->record_repo;
            $record_repo->destroy($record);
        }

        $request->getFlash()->addMessage(__('Station deleted.'), Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
    }

    public function cloneAction(ServerRequest $request, Response $response, $id): ResponseInterface
    {
        $record = $this->record_repo->find((int)$id);
        if (!($record instanceof Entity\Station)) {
            throw new NotFoundException(__('Station not found.'));
        }

        if (false !== $this->clone_form->process($request, $record)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->clone_form,
            'render_mode' => 'edit',
            'title' => __('Clone Station: %s', $record->getName()),
        ]);
    }
}
