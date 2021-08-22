<?php

class Station_RemoteRelaysCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editRemoteRelays(FunctionalTester $I): void
    {
        $testStation = $this->getTestStation();
        $station_id = $testStation->getId();

        $I->amOnPage('/station/' . $station_id . '/remotes');

        $I->see('Remote Relays');
    }
}
