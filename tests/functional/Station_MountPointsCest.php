<?php

class Station_MountPointsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editMountPoints(FunctionalTester $I): void
    {
        $testStation = $this->getTestStation();
        $station_id = $testStation->getId();

        $I->amOnPage('/station/' . $station_id . '/mounts');

        $I->see('Mount Points');
    }
}
