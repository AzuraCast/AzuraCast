<?php

class Api_Stations_PlaylistsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function managePlaylists(FunctionalTester $I): void
    {
        $I->wantTo('Manage station playlists via API.');

        $station = $this->getTestStation();

        $this->testCrudApi(
            $I,
            '/api/station/' . $station->getId() . '/playlists',
            [
                'name' => 'General Rotation Playlist',
                'source' => \App\Entity\StationPlaylist::SOURCE_SONGS,
                'type' => \App\Entity\StationPlaylist::TYPE_DEFAULT,
                'weight' => 5,
            ],
            [
                'name' => 'Modified Playlist',
                'type' => \App\Entity\StationPlaylist::TYPE_ADVANCED,
            ]
        );
    }
}
