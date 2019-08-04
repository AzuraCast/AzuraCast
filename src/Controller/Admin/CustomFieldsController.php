<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\EntityForm;
use App\Form\EntityFormManager;
use App\Http\Request;
use App\Http\Response;
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

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $records = $this->record_repo->fetchArray(true, 'name');

        return $request->getView()->renderToResponse($response, 'admin/custom_fields/index', [
            'records' => $records,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Custom Field')), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Custom Field'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Custom Field')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
    }
}
