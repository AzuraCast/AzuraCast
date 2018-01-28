<?php
namespace Controller\Admin;

use Entity;
use Slim\Http\Request;
use Slim\Http\Response;

class PermissionsController extends BaseController
{
    public function indexAction(Request $request, Response $response): Response
    {
        $all_roles = $this->em->createQuery('SELECT r, rp, s FROM Entity\Role r 
            LEFT JOIN r.users u LEFT JOIN r.permissions rp LEFT JOIN rp.station s 
            ORDER BY r.id ASC')
            ->getArrayResult();

        $roles = [];

        foreach ($all_roles as $role) {
            $role['permissions_global'] = [];
            $role['permissions_station'] = [];

            foreach ($role['permissions'] as $permission) {
                if ($permission['station']) {
                    $role['permissions_station'][$permission['station']['name']][] = $permission['action_name'];
                } else {
                    $role['permissions_global'][] = $permission['action_name'];
                }
            }

            $roles[] = $role;
        }

        $this->view->roles = $roles;
        return $this->render($response, 'admin/permissions/index');
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        /** @var Entity\Repository\BaseRepository $role_repo */
        $role_repo = $this->em->getRepository(Entity\Role::class);

        /** @var Entity\Repository\RolePermissionRepository $permission_repo */
        $permission_repo = $this->em->getRepository(Entity\RolePermission::class);

        $form = new \App\Form($this->config->forms->role->toArray());

        if (!empty($id)) {
            $record = $role_repo->find($id);
            $record_info = $role_repo->toArray($record, true, true);

            $actions = $permission_repo->getActionsForRole($record);

            $form->setDefaults(array_merge($record_info, $actions));
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\Role)) {
                $record = new Entity\Role;
            }

            $role_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();

            $permission_repo->setActionsForRole($record, $data);

            $this->alert('<b>' . _('Record updated.') . '</b>', 'green');

            return $this->redirectToName($response, 'admin:permissions:index');
        }

        return $this->renderForm($response, $form, 'edit', _('Edit Record'));
    }

    public function deleteAction(Request $request, Response $response, $id): Response
    {
        /** @var Entity\Repository\BaseRepository $role_repo */
        $role_repo = $this->em->getRepository(Entity\Role::class);

        $record = $role_repo->find((int)$id);
        if ($record instanceof Entity\Role) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->alert('<b>' . _('Record deleted.') . '</b>', 'green');
        return $this->redirectToName($response, 'admin:permissions:index');
    }
}