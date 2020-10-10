<?php

class D00_Api_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkApiIndex(FunctionalTester $I): void
    {
        $I->wantTo('Check basic API functions.');

        $I->sendGET('/api/status');
        $I->seeResponseContainsJson([
            'online' => true,
        ]);

        $I->sendGET('/api/time');
        $I->seeResponseCodeIs(200);
    }
}
