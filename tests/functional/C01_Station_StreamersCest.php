<?php
class C01_Station_StreamersCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editStreamers(FunctionalTester $I)
    {
        $I->wantTo('Edit station streamers.');

        $station_id = $this->test_station->getId();
        $I->amOnPage('/station/'.$station_id.'/streamers');

        $I->see('Streamer/DJ Accounts');
        $I->click('Enable Streaming');

        $I->click('.btn-float'); // Plus sign

        $I->submitForm('.form', [
            'streamer_username' => 'teststreamer',
            'streamer_password' => 't3ststr34m3r',
            'comments'          => 'Test Streamer',
        ]);

        $I->seeCurrentUrlEquals('/station/'.$station_id.'/streamers');

        $I->seeInSource('teststreamer');
        $I->seeInSource('Test Streamer');
    }
}
