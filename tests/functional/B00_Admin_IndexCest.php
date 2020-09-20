<?php

class B00_Admin_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function seeAdminHomepage(FunctionalTester $I): void
    {
        $I->wantTo('See the administration homepage.');

        $I->amOnPage('/admin');
        $I->see('Administration');

        $I->see('System Maintenance');
        $I->see('Users');
        $I->see('Stations');
    }
}
