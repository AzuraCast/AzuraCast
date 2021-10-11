<?php

class Frontend_SetupCest extends CestAbstract
{
    /**
     * @before setupIncomplete
     * @after setupRegister
     * @after setupStation
     * @after setupSettings
     */
    public function setupStart(FunctionalTester $I): void
    {
        $I->wantTo('Complete the initial setup process.');

        $I->amOnPage('/');

        $I->see('Setup');
        $I->see('Super Administrator');
        $I->seeCurrentUrlEquals('/setup/register');

        $I->comment('Setup redirect found.');
    }

    protected function setupRegister(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', [
            'username' => $this->login_username,
            'password' => $this->login_password,
        ]);

        $I->seeInSource('continue the setup process');
        $I->seeInRepository(\App\Entity\User::class, ['email' => $this->login_username]);

        $I->comment('User account created.');

        // $this->login_cookie = $I->grabCookie('PHPSESSID');
    }

    protected function setupStation(FunctionalTester $I): void
    {
        $I->seeCurrentUrlEquals('/setup/station');

        $I->see('continue the setup process');

        $I->submitForm('.form', [
            'name' => 'Functional Test Radio',
            'description' => 'Test radio station.',
        ]);

        $I->comment('Station created.');

        $I->seeCurrentUrlEquals('/setup/settings');
    }

    protected function setupSettings(FunctionalTester $I): void
    {
        $I->seeResponseCodeIs(200);
        $I->seeInTitle('System Settings');

        $this->setupCompleteSettings($I);

        $I->amOnPage('/dashboard');
        $I->seeResponseCodeIs(200);
    }
}
