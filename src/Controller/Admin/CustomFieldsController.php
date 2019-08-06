<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\EntityFormManager;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $records = $this->record_repo->fetchArray(true, 'name');

        return RequestHelper::getView($request)->renderToResponse($response, 'admin/custom_fields/index', [
            'records' => $records,
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            RequestHelper::getSession($request)->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Custom Field')), 'green');
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:custom_fields:index'));
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Custom Field'))
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Custom Field')) . '</b>', 'green');

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:custom_fields:index'));
    }
}
