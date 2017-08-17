<?php
class A04_Frontend_PublicCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function seePublicPage(FunctionalTester $I)
    {
        $I->wantTo('Verify that the public page displays.');

        $I->amOnPage('/public/'.$this->test_station->getId());

        $I->seeCurrentUrlEquals('/public/'.$this->test_station->getId());
        $I->see($this->test_station->getName());
    }
}