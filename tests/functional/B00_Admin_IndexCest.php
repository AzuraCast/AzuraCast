<?php
class B00_Admin_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
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

    /**
     * @before setupComplete
     * @before login
     */
    public function runNowPlayingSync(FunctionalTester $I)
    {
        $I->wantTo('Run now-playing synchronization task.');

        $I->amOnPage('/admin/sync/nowplaying');
        $I->seeInSource('Sync task complete. See log above.');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function runShortSync(FunctionalTester $I)
    {
        $I->wantTo('Run short synchronization task.');

        $I->amOnPage('/admin/sync/short');
        $I->seeInSource('Sync task complete. See log above.');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function runMediumSync(FunctionalTester $I)
    {
        $I->wantTo('Run medium synchronization task.');

        $I->amOnPage('/admin/sync/medium');
        $I->seeInSource('Sync task complete. See log above.');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function runLongSync(FunctionalTester $I)
    {
        $I->wantTo('Run long synchronization task.');

        $I->amOnPage('/admin/sync/long');
        $I->seeInSource('Sync task complete. See log above.');
    }
}