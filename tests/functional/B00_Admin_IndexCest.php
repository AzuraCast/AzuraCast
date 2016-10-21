<?php
class B00_Admin_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     * @after runSync
     * @after cleanup
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

    protected function runSync(FunctionalTester $I)
    {
        $I->amOnPage('/admin/sync/nowplaying');
        $I->seeInSource('Sync task complete. See log above.');
    }
}