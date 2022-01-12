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
                'name'   => 'General Rotation Playlist',
                'source' => App\Entity\Enums\PlaylistSources::Songs->value,
                'type'   => App\Entity\Enums\PlaylistTypes::Standard->value,
                'weight' => 5,
            ],
            [
                'name' => 'Modified Playlist',
                'type' => App\Entity\Enums\PlaylistTypes::Advanced->value,
            ]
        );
    }
}
