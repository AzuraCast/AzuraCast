<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_Frontend_DashboardCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function checkDashboard(FunctionalTester $I): void
    {
        $I->wantTo('Check dashboard API functions.');

        $I->sendGet('/api/frontend/dashboard/charts');

        $I->seeResponseCodeIs(200);
        $I->canSeeResponseContainsJson(
            [
                'metrics' => [],
            ]
        );

        $I->sendGet('/api/frontend/dashboard/notifications');

        $I->seeResponseCodeIs(200);

        $I->sendGet('/api/frontend/dashboard/stations');

        $I->seeResponseCodeIs(200);
    }
}
