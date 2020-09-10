<?php
class A01_Frontend_ProfileCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function setProfileInfo(FunctionalTester $I)
    {
        $I->wantTo('Set a user profile.');

        $I->amOnPage('/dashboard');
        $I->see('Dashboard');

        $I->amOnPage('/profile');
        $I->see('Profile');
        $I->see('Super Administrator');

        $I->click('Edit');

        $I->submitForm('.form', [
            'locale' => 'fr_FR.UTF-8',
        ]);

        $I->seeCurrentUrlEquals('/profile');
        $I->see('Français');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function changeProfileLocale(FunctionalTester $I)
    {
        $I->wantTo('Use a specific locale for a user.');

        $I->amOnPage('/profile/edit');
        $I->see('Edit Profile', '.card-title');

        $I->submitForm('.form', [
            'locale' => 'de_DE.UTF-8',
        ]);

        $I->seeCurrentUrlEquals('/profile');
        $I->see('Deutsch');
        $I->seeInTitle('Mein Account');
    }
}
