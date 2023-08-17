<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Station_IndexCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function viewIndex(FunctionalTester $I): void
    {
        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        $I->wantTo('See a per-station management panel.');

        $I->amOnPage('/station/' . $stationId);
        $I->seeResponseCodeIs(200);
        $I->seeInTitle($testStation->getName());
    }
}
