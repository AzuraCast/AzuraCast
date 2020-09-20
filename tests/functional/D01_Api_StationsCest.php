<?php

class D01_Api_StationsCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkApiStation(FunctionalTester $I): void
    {
        $I->wantTo('Check station API endpoints.');

        $testStation = $this->getTestStation();
        $station_id = $testStation->getId();

        $I->sendGET('/api/stations');
        $I->seeResponseContainsJson([
            'name' => $testStation->getName(),
        ]);

        $I->sendGET('/api/station/' . $station_id);
        $I->seeResponseContainsJson([
            'name' => $testStation->getName(),
        ]);
    }
}
