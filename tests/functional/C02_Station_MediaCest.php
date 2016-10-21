<?php
class C01_Station_MediaCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editMedia(FunctionalTester $I)
    {
        $I->wantTo('Upload a song to a station.');

        $station_id = $this->test_station->id;
        $I->amOnPage('/station/'.$station_id.'/files');


    }
}
