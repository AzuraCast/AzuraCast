<?php

namespace Functional;

class Station_ReportsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function viewReports(\FunctionalTester $I): void
    {
        $I->wantTo('View station reports.');

        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        $I->amOnPAge('/station/' . $stationId . '/reports/overview');

        $I->seeResponseCodeIs(200);
        $I->see('Station Statistics');

        $I->amOnPage('/station/' . $stationId . '/reports/timeline');

        $I->seeResponseCodeIs(200);
        $I->see('Song Playback Timeline');

        $I->amOnPage('/station/' . $stationId . '/reports/requests');

        $I->seeResponseCodeIs(200);
        $I->see('Song Requests');

        $I->amOnPage('/station/' . $stationId . '/reports/listeners');

        $I->seeResponseCodeIs(200);
        $I->see('Listeners');

        $I->amOnPage('/station/' . $stationId . '/reports/soundexchange');
        $I->seeResponseCodeIs(200);
        $I->see('SoundExchange Report');
    }
}
