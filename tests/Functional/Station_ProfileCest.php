<?php

namespace Functional;

class Station_ProfileCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editStationProfile(\FunctionalTester $I): void
    {
        $I->wantTo('View and edit a station profile.');

        $testStation = $this->getTestStation();
        $station_id = $testStation->getId();

        $I->amOnPage('/station/' . $station_id . '/profile');

        $I->see('Functional Test Radio');
        /*
         * TODO: Implement acceptance testing with Vue rendering
        $I->wantTo('Edit a station profile.');


        $I->amOnPage('/station/' . $station_id . '/profile/edit');

        $I->submitForm('.form', [
            'name' => 'Profile Update Test Radio',
            'description' => 'Testing a profile update.',
        ]);

        $I->seeCurrentUrlEquals('/station/' . $station_id . '/profile');

        $I->see('Profile Update Test Radio');
        */
    }
}
