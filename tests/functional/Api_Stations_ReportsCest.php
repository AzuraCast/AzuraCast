<?php

class Api_Stations_ReportsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function viewReports(FunctionalTester $I): void
    {
        $I->wantTo('View various station reports via API.');

        $station = $this->getTestStation();
        $uriBase = '/api/station/' . $station->getId();

        $I->sendGet($uriBase . '/reports/overview/charts');

        $I->seeResponseCodeIs(200);

        $I->sendGet($uriBase . '/reports/overview/best-and-worst');

        $I->seeResponseCodeIs(200);

        $I->sendGet($uriBase . '/reports/overview/most-played');

        $I->seeResponseCodeIs(200);
    }
}
