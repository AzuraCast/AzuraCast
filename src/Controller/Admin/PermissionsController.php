<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\PermissionsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class PermissionsController extends AbstractAdminCrudController
{
    public function __construct(
        FactoryInterface $factory
    ) {
        parent::__construct($factory->make(PermissionsForm::class));

        $this->csrf_namespace = 'admin_permissions';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $all_roles = $this->em->createQuery(
            <<<'DQL'
                SELECT r, rp, s
                FROM App\Entity\Role r
                LEFT JOIN r.users u
                LEFT JOIN r.permissions rp
                LEFT JOIN rp.station s
                ORDER BY r.id ASC
            DQL
        )->getArrayResult();

        $roles = [];

        $actions = $request->getAcl()->listPermissions();

        foreach ($all_roles as $role) {
            $role['permissions_global'] = [];
            $role['permissions_station'] = [];

            foreach ($role['permissions'] as $permission) {
                if ($permission['station']) {
                    // phpcs:disable Generic.Files.LineLength
                    $role['permissions_station'][$permission['station']['name']][] = $actions['station'][$permission['action_name']];
                    // phpcs:enable
                } else {
                    $role['permissions_global'][] = $actions['global'][$permission['action_name']];
                }
            }

            $roles[] = $role;
        }

        return $request->getView()->renderToResponse($response, 'admin/permissions/index', [
            'roles' => $roles,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, int $id = null): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage(
                '<b>' . ($id ? __('Permission updated.') : __('Permission added.')) . '</b>',
                Flash::SUCCESS
            );
            return $response->withRedirect((string)$request->getRouter()->named('admin:permissions:index'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $this->form,
                'render_mode' => 'edit',
                'title' => $id ? __('Edit Permission') : __('Add Permission'),
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $id,
        string $csrf
    ): ResponseInterface {
        $this->doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('Permission deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect((string)$request->getRouter()->named('admin:permissions:index'));
    }
}
