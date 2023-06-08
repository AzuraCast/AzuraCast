<?php

namespace Functional;

class Station_MediaCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editMedia(\FunctionalTester $I): void
    {
        $I->wantTo('Upload a song to a station.');

        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        // Upload test song
        $testSongOrig = $this->environment->getBaseDirectory() . '/resources/error.mp3';
        $I->sendPOST(
            '/api/station/' . $stationId . '/files',
            [
                'path' => 'error.mp3',
                'file' => base64_encode(file_get_contents($testSongOrig)),
            ]
        );

        $I->seeResponseContainsJson(
            [
                'title' => 'AzuraCast is Live!',
                'artist' => 'AzuraCast.com',
            ]
        );

        $I->sendGET('/api/station/' . $stationId . '/files/list');

        $I->seeResponseContainsJson(
            [
                'text' => 'AzuraCast.com - AzuraCast is Live!',
            ]
        );

        $I->amOnPage('/station/' . $stationId . '/files');

        $I->see('Music Files');
    }
}
