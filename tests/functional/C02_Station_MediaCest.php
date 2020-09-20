<?php

use App\Settings;

class C02_Station_MediaCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editMedia(FunctionalTester $I): void
    {
        $I->wantTo('Upload a song to a station.');

        $testStation = $this->getTestStation();
        $station_id = $testStation->getId();

        // Upload test song
        $test_song_orig = $this->settings[Settings::BASE_DIR] . '/resources/error.mp3';
        $I->sendPOST('/api/station/' . $station_id . '/files', [
            'path' => 'error.mp3',
            'file' => base64_encode(file_get_contents($test_song_orig)),
        ]);

        $I->seeResponseContainsJson([
            'title' => 'AzuraCast is Live!',
            'artist' => 'AzuraCast.com',
        ]);

        $I->sendGET('/api/station/' . $station_id . '/files/list');

        $I->seeResponseContainsJson([
            'media_name' => 'AzuraCast.com - AzuraCast is Live!',
        ]);

        $I->amOnPage('/station/' . $station_id . '/files');

        $I->see('Music Files');
    }
}
