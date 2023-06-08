<?php

namespace Functional;

class Station_RemoteRelaysCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editRemoteRelays(\FunctionalTester $I): void
    {
        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        $I->amOnPage('/station/' . $stationId . '/remotes');

        $I->see('Remote Relays');
    }
}
