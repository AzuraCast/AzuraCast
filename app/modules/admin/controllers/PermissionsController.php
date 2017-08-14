<?php
namespace Controller\Admin;

use Entity;

class PermissionsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer permissions');
    }

    public function indexAction()
    {
        $all_roles = $this->em->createQuery('SELECT r, rp, s FROM Entity\Role r LEFT JOIN r.users u LEFT JOIN r.permissions rp LEFT JOIN rp.station s ORDER BY r.id ASC')
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
    }

    public function membersAction()
    {
        $roles = $this->em->createQuery('SELECT r, a, u FROM Entity\Role r LEFT JOIN r.actions a LEFT JOIN r.users u')
            ->getArrayResult();

        $this->view->roles = $roles;
    }

    public function editAction()
    {
        /** @var \App\Doctrine\Repository $role_repo */
        $role_repo = $this->em->getRepository(Entity\Role::class);

        /** @var Entity\Repository\RolePermissionRepository $permission_repo */
        $permission_repo = $this->em->getRepository(Entity\RolePermission::class);

        $form = new \App\Form($this->config->forms->role->toArray());

        if ($this->hasParam('id')) {
            $record = $role_repo->find($this->getParam('id'));
            $record_info = $role_repo->toArray($record, true, true);

            $actions = $permission_repo->getActionsForRole($record);

            $form->setDefaults(array_merge($record_info, $actions));
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

            return $this->redirectFromHere(['action' => 'index', 'id' => null, 'csrf' => null]);
        }

        return $this->renderForm($form, 'edit', _('Edit Record'));
    }

    public function deleteAction()
    {
        /** @var \App\Doctrine\Repository $role_repo */
        $role_repo = $this->em->getRepository(Entity\Role::class);

        $record = $role_repo->find((int)$this->getParam('id'));
        if ($record instanceof Entity\Role) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null, 'csrf' => null]);
    }
}