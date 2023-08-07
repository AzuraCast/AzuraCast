<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_Admin_DebugCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function syncTasks(FunctionalTester $I)
    {
        $I->wantTo('Test All Synchronized Tasks');

        $I->sendPUT('/api/admin/debug/sync/all');
        $I->seeResponseCodeIs(200);
    }
}
