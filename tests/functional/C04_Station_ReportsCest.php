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

        $station_id = $this->test_station->getId();

        $I->amOnPAge('/station/'.$station_id.'/reports/overview');

        $I->seeResponseCodeIs(200);
        $I->see('Statistics Overview');

        $I->amOnPage('/station/'.$station_id.'/reports/timeline');

        $I->seeResponseCodeIs(200);
        $I->see('Song Playback Timeline');

        $I->amOnPage('/station/'.$station_id.'/reports/performance');

        $I->seeResponseCodeIs(200);
        $I->see('Song Listener Impact');

        $I->amOnPage('/station/'.$station_id.'/reports/duplicates');

        $I->seeResponseCodeIs(200);
        $I->see('No duplicates were found. Nice work!');

        $I->amOnPage('/station/'.$station_id.'/reports/requests');

        $I->seeResponseCodeIs(200);
        $I->see('Song Requests');

        $I->amOnPage('/station/'.$station_id.'/reports/listeners');

        $I->seeResponseCodeIs(200);
        $I->see('Listeners');
    }
}
