<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\EntityFormManager;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'admin/custom_fields/index', [
            'records' => $records,
            'csrf' => \App\Http\RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            \App\Http\RequestHelper::getSession($request)->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Custom Field')), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Custom Field'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Custom Field')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->named('admin:custom_fields:index'));
    }
}
