<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Station_PlaylistsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editPlaylists(FunctionalTester $I): void
    {
        $I->wantTo('Create a station playlist.');

        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        $I->amOnPage('/station/' . $stationId . '/playlists');

        $I->see('Playlists');
    }
}
