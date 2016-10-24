<?php
class D03_Api_NowPlayingCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function checkNowPlayingAPI(FunctionalTester $I)
    {
        $I->wantTo('Check now-playing API endpoints.');

        // Generate now-playing cache data.
        ob_start();
        $sync = $this->di->get('sync');
        $sync->syncNowplaying(true);
        ob_end_clean();

        $I->sendGET('/api/nowplaying');

        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(\Entity\Station::api($this->test_station));
    }
}
