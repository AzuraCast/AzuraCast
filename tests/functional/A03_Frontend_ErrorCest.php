<?php
class A03_Frontend_ErrorCest extends CestAbstract
{
    public function seeErrorPages(FunctionalTester $I)
    {
        $I->wantTo('Verify error code pages.');

        $I->amOnPage('/azurafake');
        $I->seeResponseCodeIs(404);
        $I->see('404');
    }
}