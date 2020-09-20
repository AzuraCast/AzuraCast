<?php

class A04_Frontend_PublicCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function seePublicPage(FunctionalTester $I): void
    {
        $I->wantTo('Verify that the public page displays.');

        $testStation = $this->getTestStation();

        $I->amOnPage('/public/' . $testStation->getId());

        $I->seeCurrentUrlEquals('/public/' . $testStation->getId());
        $I->see($testStation->getName());
    }
}