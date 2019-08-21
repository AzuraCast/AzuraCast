<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\EntityFormManager;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;

class CustomFieldsController extends AbstractAdminCrudController
{
    /**
     * @param Config $config
     * @param EntityFormManager $formManager
     */
    public function __construct(Config $config, EntityFormManager $formManager)
    {
        $form = $formManager->getForm(Entity\CustomField::class, $config->get('forms/custom_field'));
        parent::__construct($form);

        $this->csrf_namespace = 'admin_custom_fields';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $records = $this->record_repo->fetchArray(true, 'name');

        return $request->getView()->renderToResponse($response, 'admin/custom_fields/index', [
            'records' => $records,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash(($id ? __('Custom Field updated.') : __('Custom Field added.')), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => $id ? __('Edit Custom Field') : __('Add Custom Field')
        ]);
    }

    public function deleteAction(ServerRequest $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('Custom Field deleted.') . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
    }
}
