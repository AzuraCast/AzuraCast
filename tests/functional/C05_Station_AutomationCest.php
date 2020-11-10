<?php

use App\Entity;

class C05_Station_AutomationCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function viewAutomation(FunctionalTester $I): void
    {
        $I->wantTo('Test station automation.');

        // Set up automation preconditions.
        $testStation = $this->getTestStation();

        $playlist = new Entity\StationPlaylist($testStation);
        $playlist->setName('Test Playlist');
        $playlist->setIncludeInAutomation(true);

        $this->em->persist($playlist);

        $media = $this->uploadTestSong();
        $spm = new Entity\StationPlaylistMedia($playlist, $media);
        $this->em->persist($spm);

        $this->em->flush();

        $station_id = $testStation->getId();
        $this->em->clear();

        // Attempt to enable and run automation.
        $I->amOnPage('/station/' . $station_id . '/automation');

        $I->submitForm('.form', [
            'is_enabled' => '1',
        ]);

        $I->seeCurrentUrlEquals('/station/' . $station_id . '/automation');
        $I->click('Run Automated Assignment');

        $I->seeInSource('Automated assignment complete!');
    }
}
