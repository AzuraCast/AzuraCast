<?php
namespace App\Controller\Admin;

use App\Acl;
use App\Form\PermissionsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PermissionsController extends AbstractAdminCrudController
{
    /**
     * @param PermissionsForm $form
     */
    public function __construct(PermissionsForm $form)
    {
        parent::__construct($form);
        $this->csrf_namespace = 'admin_permissions';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
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

        return $request->getView()->renderToResponse($response, 'admin/permissions/index', [
            'roles' => $roles,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Permission')) . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->named('admin:permissions:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Permission')),
        ]);
    }

    public function deleteAction(ServerRequest $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Permission')) . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->named('admin:permissions:index'));
    }
}
