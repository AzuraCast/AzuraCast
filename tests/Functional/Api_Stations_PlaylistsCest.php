<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use FunctionalTester;

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
            '/api/station/' . $station->id . '/playlists',
            [
                'name' => 'General Rotation Playlist',
                'source' => PlaylistSources::Songs->value,
                'type' => PlaylistTypes::Standard->value,
                'weight' => 5,
            ],
            [
                'name' => 'Modified Playlist',
                'type' => PlaylistTypes::Advanced->value,
            ]
        );
    }
}
