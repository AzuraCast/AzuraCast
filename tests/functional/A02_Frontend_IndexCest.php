<?php

class A02_Frontend_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function seeHomepage(FunctionalTester $I): void
    {
        $I->wantTo('See the proper data on the homepage.');

        $I->amOnPage('/dashboard');
        $I->see('Dashboard');

        $I->see('Listeners Per Station');
    }
}
