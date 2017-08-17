<?php
class C05_Station_AutomationCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function viewAutomation(FunctionalTester $I)
    {
        $I->wantTo('Test station automation.');

        // Set up automation preconditions.
        $song_src = APP_INCLUDE_ROOT.'/resources/error.mp3';
        $song_dest = $this->test_station->getRadioMediaDir().'/test.mp3';
        copy($song_src, $song_dest);

        $playlist = new \Entity\StationPlaylist($this->test_station);
        $playlist->setName('Test Playlist');
        $playlist->setIncludeInAutomation(true);

        $this->em->persist($playlist);

        $media = new \Entity\StationMedia($this->test_station, 'test.mp3');
        $media->getPlaylists()->add($playlist);
        $media->loadFromFile();

        $this->em->persist($media);
        $this->em->flush();

        $this->em->refresh($this->test_station);
        $this->em->refresh($playlist);

        // Attempt to enable and run automation.
        $station_id = $this->test_station->getId();

        $I->amOnPage('/station/'.$station_id.'/automation');

        $I->submitForm('.form', [
            'is_enabled' => '1',
        ]);

        $I->seeCurrentUrlEquals('/station/'.$station_id.'/automation');
        $I->click('Run Automated Assignment');

        $I->seeInSource('Automated assignment complete!');
    }
}
