<?php
class A04_Frontend_PublicCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function seePublicPage(FunctionalTester $I)
    {
        $I->wantTo('Verify that the public page displays.');

        $I->amOnPage('/public/'.$this->test_station->id);

        $I->seeCurrentUrlEquals('/public/'.$this->test_station->id);
        $I->see($this->test_station->name);
    }
}