<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Frontend_ErrorCest extends CestAbstract
{
    public function seeErrorPages(FunctionalTester $I): void
    {
        $I->wantTo('Verify error code pages.');

        $I->amOnPage('/azurafake');
        $I->seeResponseCodeIs(404);
        $I->see('404');
    }
}
