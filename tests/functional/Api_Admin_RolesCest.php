<?php

class Api_Admin_RolesCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageRoles(FunctionalTester $I): void
    {
        $I->wantTo('Manage roles via API.');

        $this->testCrudApi(
            $I,
            '/api/admin/roles',
            [
                'name' => 'Generic Admin',
                'permissions' => [
                    'global' => [
                        App\Enums\GlobalPermissions::All->value,
                    ],
                ],
            ],
            [
                'name' => 'Test Generic Administrator',
            ]
        );
    }

    public function checkSuperAdminRole(FunctionalTester $I): void
    {
        $I->wantTo('Ensure super administrator is not editable.');

        $permissionRepo = $this->di->get(App\Entity\Repository\RolePermissionRepository::class);
        $superAdminRole = $permissionRepo->ensureSuperAdministratorRole();

        $I->sendPut(
            '/api/admin/role/' . $superAdminRole->getIdRequired(),
            [
                'name' => 'Edited Role',
            ]
        );

        $I->seeResponseCodeIsClientError();

        $I->sendDelete(
            '/api/admin/role/' . $superAdminRole->getIdRequired(),
        );

        $I->seeResponseCodeIsClientError();
    }
}
