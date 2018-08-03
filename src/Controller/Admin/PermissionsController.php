<?php
namespace App\Controller\Admin;

use App\Csrf;
use App\Flash;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class PermissionsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var array */
    protected $actions;

    /** @var array */
    protected $form_config;

    /** @var Csrf */
    protected $csrf;

    /** @var string */
    protected $csrf_namespace = 'admin_permissions';

    public function __construct(EntityManager $em, Flash $flash, Csrf $csrf, array $actions, array $form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->actions = $actions;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $all_roles = $this->em->createQuery('SELECT r, rp, s FROM '.Entity\Role::class.' r 
            LEFT JOIN r.users u LEFT JOIN r.permissions rp LEFT JOIN rp.station s 
            ORDER BY r.id ASC')
            ->getArrayResult();

        $roles = [];

        foreach ($all_roles as $role) {
            $role['permissions_global'] = [];
            $role['permissions_station'] = [];

            foreach ($role['permissions'] as $permission) {
                if ($permission['station']) {
                    $role['permissions_station'][$permission['station']['name']][] = $this->actions['station'][$permission['action_name']];
                } else {
                    $role['permissions_global'][] = $this->actions['global'][$permission['action_name']];
                }
            }

            $roles[] = $role;
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'admin/permissions/index', [
            'roles' => $roles,
            'csrf' => $this->csrf->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        /** @var Entity\Repository\BaseRepository $role_repo */
        $role_repo = $this->em->getRepository(Entity\Role::class);

        /** @var Entity\Repository\RolePermissionRepository $permission_repo */
        $permission_repo = $this->em->getRepository(Entity\RolePermission::class);

        $form = new \AzuraForms\Form($this->form_config);

        if (!empty($id)) {
            $record = $role_repo->find($id);
            $record_info = $role_repo->toArray($record, true, true);

            $actions = $permission_repo->getActionsForRole($record);

            $form->populate(array_merge($record_info, $actions));
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

            $this->flash->alert('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Permission')) . '</b>', 'green');

            return $response->redirectToRoute('admin:permissions:index');
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Permission')),
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\Repository\BaseRepository $role_repo */
        $role_repo = $this->em->getRepository(Entity\Role::class);

        $record = $role_repo->find((int)$id);
        if ($record instanceof Entity\Role) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->flash->alert('<b>' . __('%s deleted.', __('Permission')) . '</b>', 'green');
        return $response->redirectToRoute('admin:permissions:index');
    }
}
