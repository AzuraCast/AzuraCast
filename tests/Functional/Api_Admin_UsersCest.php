<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_Admin_UsersCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageUsers(FunctionalTester $I): void
    {
        $I->wantTo('Manage users via API.');

        $this->testCrudApi(
            $I,
            '/api/admin/users',
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            [
                'name' => 'Test User Renamed',
            ]
        );
    }
}
