<?php
class D02_Api_RequestsCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkRequestsAPI(FunctionalTester $I)
    {
        $I->wantTo('Check request API endpoints.');

        // Enable requests on station.
        $this->test_station->setEnableRequests(true);
        $this->em->persist($this->test_station);
        $this->em->flush();

        // Upload a test song.
        $song_src = APP_INCLUDE_ROOT.'/resources/error.mp3';
        $song_dest = $this->test_station->getRadioMediaDir().'/test.mp3';
        copy($song_src, $song_dest);

        $playlist = new \Entity\StationPlaylist($this->test_station);
        $playlist->setName('Test Playlist');

        $this->em->persist($playlist);

        $media = new \Entity\StationMedia($this->test_station, 'test.mp3');
        $media->getPlaylists()->add($playlist);
        $media->loadFromFile();

        $this->em->persist($media);
        $this->em->flush();

        $station_id = $this->test_station->getId();

        $I->sendGET('/api/station/'.$station_id.'/requests');

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $I->sendGET('/api/station/'.$station_id.'/request/'.$media->getId());

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }
}
