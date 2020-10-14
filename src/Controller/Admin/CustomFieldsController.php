<?php

namespace App\Controller\Admin;

use App\Entity;
use App\Form\CustomFieldForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class CustomFieldsController extends AbstractAdminCrudController
{
    public function __construct(CustomFieldForm $form)
    {
        parent::__construct($form);

        $this->csrf_namespace = 'admin_custom_fields';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $records = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Entity\CustomField::class, 'e')
            ->orderBy('e.name')
            ->getQuery()->getArrayResult();

        return $request->getView()->renderToResponse($response, 'admin/custom_fields/index', [
            'records' => $records,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage(
                ($id ? __('Custom Field updated.') : __('Custom Field added.')),
                Flash::SUCCESS
            );
            return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => $id ? __('Edit Custom Field') : __('Add Custom Field'),
        ]);
    }

    public function deleteAction(ServerRequest $request, Response $response, $id, $csrf): ResponseInterface
    {
        $this->doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('Custom Field deleted.') . '</b>', Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
    }
}
