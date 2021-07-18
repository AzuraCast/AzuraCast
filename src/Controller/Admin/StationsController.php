<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Exception\NotFoundException;
use App\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class StationsController extends AbstractAdminCrudController
{
    public function __construct(
        protected StationRepository $stationRepo,
        protected FactoryInterface $factory
    ) {
        parent::__construct($factory->make(Form\StationForm::class));

        $this->csrf_namespace = 'admin_stations';
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $stations = $this->stationRepo->fetchArray(false, 'name');

        return $request->getView()->renderToResponse($response, 'admin/stations/index', [
            'stations' => $stations,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, int $id = null): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage(($id ? __('Station updated.') : __('Station added.')), Flash::SUCCESS);
            return $response->withRedirect((string)$request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'admin/stations/edit',
            [
                'form' => $this->form,
                'title' => $id ? __('Edit Station') : 'Add Station',
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $id,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $record = $this->record_repo->find($id);
        if ($record instanceof Entity\Station) {
            $this->stationRepo->destroy($record);
        }

        $request->getFlash()->addMessage(__('Station deleted.'), Flash::SUCCESS);
        return $response->withRedirect((string)$request->getRouter()->named('admin:stations:index'));
    }

    public function cloneAction(ServerRequest $request, Response $response, int $id): ResponseInterface
    {
        $cloneForm = $this->factory->make(Form\StationCloneForm::class);

        $record = $this->record_repo->find($id);
        if (!($record instanceof Entity\Station)) {
            throw new NotFoundException(__('Station not found.'));
        }

        if (false !== $cloneForm->process($request, $record)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect((string)$request->getRouter()->named('admin:stations:index'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $cloneForm,
                'render_mode' => 'edit',
                'title' => __('Clone Station: %s', $record->getName()),
            ]
        );
    }
}
