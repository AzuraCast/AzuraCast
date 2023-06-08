<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Station_RemoteRelaysCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editRemoteRelays(FunctionalTester $I): void
    {
        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        $I->amOnPage('/station/' . $stationId . '/remotes');

        $I->see('Remote Relays');
    }
}
