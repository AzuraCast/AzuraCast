<?php

class C03_Station_PlaylistsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editPlaylists(FunctionalTester $I): void
    {
        $I->wantTo('Create a station playlist.');

        $testStation = $this->getTestStation();
        $station_id = $testStation->getId();

        $I->amOnPage('/station/' . $station_id . '/playlists');

        $I->see('Playlists');
    }
}
