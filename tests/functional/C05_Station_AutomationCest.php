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

        $song_src = '/var/azuracast/www/resources/error.mp3';
        $song_dest = $testStation->getRadioMediaDir() . '/test.mp3';
        copy($song_src, $song_dest);

        $playlist = new Entity\StationPlaylist($testStation);
        $playlist->setName('Test Playlist');
        $playlist->setIncludeInAutomation(true);

        $this->em->persist($playlist);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->di->get(Entity\Repository\StationMediaRepository::class);

        $media = new Entity\StationMedia($testStation, 'test.mp3');
        $media_repo->loadFromFile($media, $song_dest);

        $this->em->persist($media);

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
