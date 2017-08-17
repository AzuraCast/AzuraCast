<?php
class D01_Api_StationsCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkApiStation(FunctionalTester $I)
    {
        $I->wantTo('Check station API endpoints.');

        $I->sendGET('/api/stations');
        $I->seeResponseContainsJson([
            'name' => $this->test_station->getName(),
        ]);

        $I->sendGET('/api/station/'.$this->test_station->getId());
        $I->seeResponseContainsJson([
            'name' => $this->test_station->getName(),
        ]);
    }
}
