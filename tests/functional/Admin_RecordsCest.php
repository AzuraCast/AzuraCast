<?php

class Admin_RecordsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageUsers(FunctionalTester $I): void
    {
        $I->wantTo('Manage users.');

        // User homepage
        $I->amOnPage('/admin/users');
        $I->seeResponseCodeIs(200);
        /*
         * TODO: Acceptance Testing with Vue Rendering

        $I->see($this->login_username);

        // Edit existing user
        $I->click('Edit');

        $I->submitForm('.form', []);

        $I->seeCurrentUrlEquals('/admin/users');
        $I->see($this->login_username);

        // Add a secondary user
        $I->click('Add User', '#content');

        $I->submitForm('.form', [
            'name' => 'ZZZ Test Administrator',
            'email' => 'test@azuracast.com',
            'auth_password' => 'CorrectBatteryStapleHorse',
        ]);

        $I->seeCurrentUrlEquals('/admin/users');
        $I->see('test@azuracast.com');

        // Delete the secondary user
        $I->click(\Codeception\Util\Locator::lastElement('.btn-danger'));

        $I->seeCurrentUrlEquals('/admin/users');
        $I->dontSee('test@azuracast.com');
        */
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function manageStations(FunctionalTester $I): void
    {
        $I->wantTo('Manage stations.');

        $I->amOnPage('/admin/stations');
        $I->seeResponseCodeIs(200);
        /*
         * TODO: Acceptance Testing with Vue Rendering

        $I->see('Functional Test Radio');


        $I->click('Edit');

        $I->submitForm('.form', [
            'name' => 'Modification Test Radio',
        ]);

        $I->seeCurrentUrlEquals('/admin/stations');
        $I->see('Modification Test Radio');
        */
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function manageSettings(FunctionalTester $I): void
    {
        $I->wantTo('Manage settings.');

        $I->amOnPage('/admin/settings');
        $I->seeResponseCodeIs(200);
        $I->seeInTitle('System Settings');
    }
}
