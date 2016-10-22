<?php
class B00_Admin_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     * @after runSyncs
     */
    public function seeAdminHomepage(FunctionalTester $I)
    {
        $I->wantTo('See the administration homepage.');

        $I->amOnPage('/admin');
        $I->see('Administration');

        $I->see('System Maintenance');
        $I->see('Users');
        $I->see('Stations');
    }

    protected function runSyncs(FunctionalTester $I)
    {
        $I->amOnPage('/admin/sync/nowplaying');
        $I->seeInSource('Sync task complete. See log above.');

        $I->amOnPage('/admin/sync/short');
        $I->seeInSource('Sync task complete. See log above.');

        $I->amOnPage('/admin/sync/medium');
        $I->seeInSource('Sync task complete. See log above.');

        $I->amOnPage('/admin/sync/long');
        $I->seeInSource('Sync task complete. See log above.');
    }
}