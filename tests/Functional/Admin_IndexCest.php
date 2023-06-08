<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Admin_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function seeAdminHomepage(FunctionalTester $I): void
    {
        $I->wantTo('See the administration homepage.');

        $I->amOnPage('/admin');
        $I->seeResponseCodeIs(200);
        $I->seeInTitle('Administration');
    }
}
