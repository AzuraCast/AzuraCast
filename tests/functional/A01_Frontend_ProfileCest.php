<?php
class A01_Frontend_ProfileCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     * @after cleanup
     */
    public function setProfileInfo(FunctionalTester $I)
    {
        $I->wantTo('Set a user profile.');

        $I->amOnPage('/');
        $I->see('Dashboard');

        $I->amOnPage('/profile');
        $I->see('Profile');
        $I->see('Super Administrator');

        $I->click('Edit Profile');

        $I->submitForm('.form', [
            'app_form' => [
                'timezone' => 'US/Central',
                'locale' => 'fr_FR.UTF-8',
            ]
        ]);

        $I->seeCurrentUrlEquals('/profile');
        $I->see('Central Time');
        $I->see('Français');
    }
}