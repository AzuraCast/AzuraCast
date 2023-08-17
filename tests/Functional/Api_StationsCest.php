<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_StationsCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkApiStation(FunctionalTester $I): void
    {
        $I->wantTo('Check station API endpoints.');

        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        $I->sendGET('/api/stations');
        $I->seeResponseContainsJson([
            'name' => $testStation->getName(),
        ]);

        $I->sendGET('/api/station/' . $stationId);
        $I->seeResponseContainsJson([
            'name' => $testStation->getName(),
        ]);
    }
}
