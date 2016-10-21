<?php
class A00_Frontend_SetupCest extends CestAbstract
{
    /**
     * @before setupIncomplete
     * @after setupRegister
     * @after setupStation
     * @after setupSettings
     * @after cleanup
     */
    public function setupStart(FunctionalTester $I)
    {
        $I->wantTo('Complete the initial setup process.');

        $I->amOnPage('/');

        $I->see('Begin setup');
        $I->seeCurrentUrlEquals('/setup/register');

        $I->comment('Setup redirect found.');
    }

    protected function setupRegister(FunctionalTester $I)
    {
        $I->submitForm('#login-form', [
            'username' => $this->login_username,
            'password' => $this->login_password,
        ]);

        $I->seeInSource('continue the setup process');
        $I->seeInRepository('Entity\User', ['email' => $this->login_username]);

        $I->comment('User account created.');

        // $this->login_cookie = $I->grabCookie('PHPSESSID');
    }

    protected function setupStation(FunctionalTester $I)
    {
        $I->seeCurrentUrlEquals('/setup/station');

        $I->see('continue the setup process');

        $I->submitForm('.form', [
            'app_form' => [
                'name' => 'Functional Test Radio',
                'description' => 'Test radio station.',
            ],
        ]);

        $I->comment('Station created.');

        $I->seeCurrentUrlEquals('/setup/settings');
    }

    protected function setupSettings(FunctionalTester $I)
    {
        $I->submitForm('.form', []);

        $I->seeResponseCodeIs(200);
        $I->seeCurrentUrlEquals('/');
        $I->seeInSource('Setup is now complete!');
    }
}
