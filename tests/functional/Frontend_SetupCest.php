<?php
/**
 * @group frontend
 */
class Frontend_SetupCest extends CestAbstract
{
    /**
     * @before setupIncomplete
     * @after setupRegister
     * @after setupStation
     * @after setupSettings
     */
    public function setupStart(FunctionalTester $I)
    {
        $I->wantTo('Check for a setup redirect.');
        $I->amOnPage('/');

        $I->see('Begin setup');
        $I->seeCurrentUrlEquals('/setup/register');
    }

    protected function setupRegister(FunctionalTester $I)
    {
        $I->wantTo('Create a user account.');
        $I->amOnPage('/setup/register');

        $I->submitForm('#login-form', [
            'username' => $this->login_username,
            'password' => $this->login_password,
        ]);

        $I->seeInSource('continue the setup process');
        $I->seeInRepository('Entity\User', ['email' => $this->login_username]);

        $this->login_cookie = $I->grabCookie('PHPSESSID');
    }

    protected function setupStation(FunctionalTester $I)
    {
        $I->wantTo('Set up a station.');
        $I->amOnPage('/setup/station');

        $I->seeCurrentUrlEquals('/setup/station');

        $I->see('continue the setup process');

        $I->submitForm('.form', [
            'app_form' => [
                'name' => 'Functional Test Radio',
                'description' => 'Test radio station.',
            ],
        ]);

        $I->seeCurrentUrlEquals('/setup/settings');
    }

    protected function setupSettings(FunctionalTester $I)
    {
        $I->wantTo('Set up site settings.');
        $I->amOnPage('/setup/settings');

        $I->submitForm('.form', []);

        $I->wantTo('See a set up site.');

        $I->seeResponseCodeIs(200);
        $I->seeCurrentUrlEquals('/');
        $I->seeInSource('Setup is now complete!');
    }
}
