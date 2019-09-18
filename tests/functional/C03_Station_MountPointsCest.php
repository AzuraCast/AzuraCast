<?php
class C03_Station_MountPointsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editMountPoints(FunctionalTester $I)
    {
        $I->wantTo('Create a station mount point.');

        $station_id = $this->test_station->getId();
        $I->amOnPage('/station/'.$station_id.'/mounts');

        $I->see('Mount Points');
        $I->click('add', '#content');

        $I->submitForm('.form', [
            'name' => '/test.mp3',
            'display_name' => 'Test MP3',
        ]);

        $I->seeCurrentUrlEquals('/station/'.$station_id.'/mounts');

        $I->see('Test MP3');

        $I->click(\Codeception\Util\Locator::lastElement('.btn-danger'));

        $I->seeCurrentUrlEquals('/station/'.$station_id.'/mounts');

        $I->dontSee('Test MP3');
    }
}
