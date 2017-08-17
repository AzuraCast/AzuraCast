<?php
class C00_Station_ProfileCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editStationProfile(FunctionalTester $I)
    {
        $I->wantTo('Edit a station profile.');

        $station_id = $this->test_station->getId();

        $I->amOnPage('/station/'.$station_id);

        $I->see('Listeners By Day');

        $I->amOnPage('/station/'.$station_id.'/profile');

        $I->see('Functional Test Radio');

        $I->click('.btn-float'); // Plus sign

        $I->submitForm('.form', [
            'name' => 'Profile Update Test Radio',
            'description' => 'Testing a profile update.',
        ]);

        $I->seeCurrentUrlEquals('/station/'.$station_id.'/profile');

        $I->see('Profile Update Test Radio');
        $I->see('Testing a profile update.');
    }
}
