<?php

class B03_Admin_DebugCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function runNowPlayingSync(FunctionalTester $I): void
    {
        $I->wantTo('Run now-playing synchronization task.');

        $I->amOnPage('/admin/debug/sync/nowplaying');
        $I->seeInSource('Sync Task Output');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function runShortSync(FunctionalTester $I): void
    {
        $I->wantTo('Run short synchronization task.');

        $I->amOnPage('/admin/debug/sync/short');
        $I->seeInSource('Sync Task Output');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function runMediumSync(FunctionalTester $I): void
    {
        $I->wantTo('Run medium synchronization task.');

        $I->amOnPage('/admin/debug/sync/medium');
        $I->seeInSource('Sync Task Output');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function runLongSync(FunctionalTester $I): void
    {
        $I->wantTo('Run long synchronization task.');

        $I->amOnPage('/admin/debug/sync/long');
        $I->seeInSource('Sync Task Output');
    }
}
