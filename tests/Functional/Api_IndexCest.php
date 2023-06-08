<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkApiIndex(FunctionalTester $I): void
    {
        $I->wantTo('Check basic API functions.');

        $I->sendGET('/api/status');
        $I->seeResponseContainsJson(
            [
                'online' => true,
            ]
        );

        $I->sendGET('/api/time');
        $I->seeResponseCodeIs(200);
    }
}
