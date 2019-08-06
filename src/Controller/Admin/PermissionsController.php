<?php
namespace App\Controller\Admin;

use App\Acl;
use App\Form\EntityForm;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PermissionsController extends AbstractAdminCrudController
{
    /**
     * @param EntityForm $form
     */
    public function __construct(EntityForm $form)
    {
        parent::__construct($form);
        $this->csrf_namespace = 'admin_permissions';
    }

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $all_roles = $this->em->createQuery(/** @lang DQL */'SELECT 
            r, rp, s 
            FROM App\Entity\Role r 
            LEFT JOIN r.users u 
            LEFT JOIN r.permissions rp 
            LEFT JOIN rp.station s 
            ORDER BY r.id ASC')
            ->getArrayResult();

        $roles = [];

        $actions = Acl::listPermissions();

        foreach ($all_roles as $role) {
            $role['permissions_global'] = [];
            $role['permissions_station'] = [];

            foreach ($role['permissions'] as $permission) {
                if ($permission['station']) {
                    $role['permissions_station'][$permission['station']['name']][] = $actions['station'][$permission['action_name']];
                } else {
                    $role['permissions_global'][] = $actions['global'][$permission['action_name']];
                }
            }

            $roles[] = $role;
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'admin/permissions/index', [
            'roles' => $roles,
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            RequestHelper::getSession($request)->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Permission')) . '</b>', 'green');
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:permissions:index'));
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Permission')),
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Permission')) . '</b>', 'green');
        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:permissions:index'));
    }
}
