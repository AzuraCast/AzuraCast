<?php
namespace App\Controller\Admin;

use App\Acl;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class PermissionsController
{
    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $actions;

    /** @var array */
    protected $form_config;

    /** @var string */
    protected $csrf_namespace = 'admin_permissions';

    /**
     * @param EntityManager $em
     * @param array $form_config
     * @see \App\Provider\AdminProvider
     */
    public function __construct(EntityManager $em, array $form_config)
    {
        $this->em = $em;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $all_roles = $this->em->createQuery('SELECT r, rp, s FROM '.Entity\Role::class.' r 
            LEFT JOIN r.users u LEFT JOIN r.permissions rp LEFT JOIN rp.station s 
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

    public function editAction(Request $request, Response $response, $id = null): ResponseInterface
    {
        /** @var \Azura\Doctrine\Repository $role_repo */
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

            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Permission')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->named('admin:permissions:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Permission')),
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        /** @var \Azura\Doctrine\Repository $role_repo */
        $role_repo = $this->em->getRepository(Entity\Role::class);

        $record = $role_repo->find((int)$id);
        if ($record instanceof Entity\Role) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Permission')) . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->named('admin:permissions:index'));
    }
}
