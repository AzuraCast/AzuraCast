<?php
use App\Entity;

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

        $playlist = new Entity\StationPlaylist($this->test_station);
        $playlist->setName('Test Playlist');

        $this->em->persist($playlist);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        $media = new Entity\StationMedia($this->test_station, 'test.mp3');
        $media_repo->loadFromFile($media, $song_dest);

        $this->em->persist($media);

        $spm = new Entity\StationPlaylistMedia($playlist, $media);
        $this->em->persist($spm);

        $this->em->flush();

        $this->em->refresh($media);
        $this->em->refresh($playlist);

        $station_id = $this->test_station->getId();

        $I->sendGET('/api/station/'.$station_id.'/requests');

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $I->sendGET('/api/station/'.$station_id.'/request/'.$media->getUniqueId());

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
    }
}
