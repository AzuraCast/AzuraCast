<?php
class C03_Station_PlaylistsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editPlaylists(FunctionalTester $I)
    {
        $I->wantTo('Create a station playlist.');

        $station_id = $this->test_station->id;
        $I->amOnPage('/station/'.$station_id.'/playlists');

        $I->see('Playlists');
        $I->click('.btn-float'); // Plus sign

        $I->submitForm('.form', [
            'app_form' => [
                'name'          => 'Default Playlist',
            ],
        ]);

        $I->seeCurrentUrlEquals('/station/'.$station_id.'/playlists');

        $I->see('Default Playlist');
    }
}
