<?php
class C04_Station_ReportsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function viewReports(FunctionalTester $I)
    {
        $I->wantTo('View station reports.');

        $station_id = $this->test_station->id;

        $I->amOnPage('/station/'.$station_id.'/reports/timeline');

        $I->seeResponseCodeIs(200);
        $I->see('Station Playback Timeline');

        $I->amOnPage('/station/'.$station_id.'/reports/performance');

        $I->seeResponseCodeIs(200);
        $I->see('Song Listener Impact');

        $I->amOnPage('/station/'.$station_id.'/reports/duplicates');

        $I->seeResponseCodeIs(200);
        $I->see('No duplicates were found. Nice work!');
    }
}
