<?php
class B02_Admin_RecordsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function seeUserAndRole(FunctionalTester $I)
    {
        $I->wantTo('See administration records.');

        // Users

        $I->amOnPage('/admin/users');
        $I->see($this->login_username);

        $I->click('Edit');

        $I->submitForm('.form', []);

        $I->seeCurrentUrlEquals('/admin/users');
        $I->see($this->login_username);

        // Permissions

        $I->amOnPage('/admin/permissions');
        $I->see('Super Administrator');

        $I->click('Edit');

        $I->submitForm('.form', [
            'name' => 'Test Administrator',
        ]);

        $I->seeCurrentUrlEquals('/admin/permissions');
        $I->see('Test Administrator');

        // Stations

        $I->amOnPage('/admin/stations');
        $I->see('Functional Test Radio');

        $I->click('Edit');

        $I->submitForm('.form', [
            'name' => 'Modification Test Radio',
        ]);

        $I->seeCurrentUrlEquals('/admin/stations');
        $I->see('Modification Test Radio');

        // Settings

        $I->amOnPage('/admin/settings');
        $I->submitForm('.form', []);
        $I->seeCurrentUrlEquals('/admin/settings');
    }
}