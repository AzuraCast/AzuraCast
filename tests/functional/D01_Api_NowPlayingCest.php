<?php
class D01_Api_NowPlayingCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkNowPlaying(FunctionalTester $I)
    {
        $I->wantTo('Check nowplaying API endpoint.');

        $I->sendGET('/nowplaying');
        $I->seeResponseContainsJson([
            'status' => 'success',
            /* 'station' => [
                'id' => $this->test_station->id,
                'name' => $this->test_station->name,
                'shortcode' => $this->test_station->getShortName(),
            ], */
        ]);
    }
}
