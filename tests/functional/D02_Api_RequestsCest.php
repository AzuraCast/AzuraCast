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
        $this->test_station->enable_requests = true;
        $this->em->persist($this->test_station);
        $this->em->flush();

        // Upload a test song.
        $song_src = APP_INCLUDE_ROOT.'/resources/error.mp3';
        $song_dest = $this->test_station->getRadioMediaDir().'/test.mp3';
        copy($song_src, $song_dest);

        $playlist = new \Entity\StationPlaylist();
        $playlist->fromArray($this->em, [
            'station'   => $this->test_station,
            'name'      => 'Test Playlist',
        ]);

        $this->em->persist($playlist);

        $media = new \Entity\StationMedia();
        $media->fromArray($this->em, [
            'station'   => $this->test_station,
            'path'      => 'test.mp3',
        ]);
        $media->playlists->add($playlist);
        $media->loadFromFile();

        $this->em->persist($media);
        $this->em->flush();

        $station_id = $this->test_station->id;

        $I->sendGET('/api/requests/'.$station_id.'/list');

        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
        ]);

        $I->sendGET('/api/requests/'.$station_id.'/submit/'.$media->id);

        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
        ]);
    }
}
