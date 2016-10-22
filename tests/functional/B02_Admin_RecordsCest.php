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

        $I->amOnPage('/admin/users');
        $I->see($this->login_username);

        $I->amOnPage('/admin/permissions');
        $I->see('Super Administrator');

        $I->amOnPage('/admin/stations');
        $I->see('Functional Test Radio');
    }
}