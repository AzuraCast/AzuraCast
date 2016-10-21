<?php
class D00_Api_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkApiIndex(FunctionalTester $I)
    {
        $I->wantTo('Check basic API functions.');

        $I->sendGET('');
        $I->seeResponseContainsJson([
            'status' => 'success',
        ]);

        $I->sendGET('/status');
        $I->seeResponseContainsJson([
            'online' => 'true',
        ]);

        $I->sendGET('/time');
        $I->seeResponseContainsJson([
            'gmt_timezone' => 'GMT',
        ]);
    }
}
