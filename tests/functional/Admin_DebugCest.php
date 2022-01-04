<?php

class Admin_DebugCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function syncTasks(FunctionalTester $I)
    {
        $I->wantTo('Test All Synchronized Tasks');
        $I->amOnPage('/admin/debug/sync/all');
        $I->seeResponseCodeIsSuccessful();
    }
}
