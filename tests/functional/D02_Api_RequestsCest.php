<?php

use App\Entity;

class D02_Api_RequestsCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkRequestsAPI(FunctionalTester $I): void
    {
        $I->wantTo('Check request API endpoints.');

        // Enable requests on station.
        $testStation = $this->getTestStation();
        $station_id = $testStation->getId();

        $testStation->setEnableRequests(true);
        $this->em->persist($testStation);
        $this->em->flush();

        // Upload a test song.
        $media = $this->uploadTestSong();

        $playlist = new Entity\StationPlaylist($testStation);
        $playlist->setName('Test Playlist');
        $this->em->persist($playlist);

        $spm = new Entity\StationPlaylistMedia($playlist, $media);
        $this->em->persist($spm);

        $this->em->flush();
        $this->em->clear();

        $I->sendGET('/api/station/' . $station_id . '/requests');

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $I->sendGET('/api/station/' . $station_id . '/request/' . $media->getUniqueId());

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }
}
