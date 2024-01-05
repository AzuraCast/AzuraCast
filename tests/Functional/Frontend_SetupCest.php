<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

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

        $I->seeCurrentUrlEquals('/setup/register');
        $I->seeInTitle('Set Up');

        $I->comment('Setup redirect found.');
    }

    protected function setupRegister(FunctionalTester $I): void
    {
        $I->amOnPage('/setup');

        $I->seeCurrentUrlEquals('/setup/register');
        $I->seeResponseCodeIs(200);

        $this->setupCompleteUser($I);

        $I->amOnPage('/login');

        $I->sendPost(
            '/login',
            [
                'username' => $this->login_username,
                'password' => $this->login_password,
            ]
        );
    }

    protected function setupStation(FunctionalTester $I): void
    {
        $I->amOnPage('/setup');
        $I->seeCurrentUrlEquals('/setup/station');
        $I->seeResponseCodeIs(200);

        $this->setupCompleteStations($I);
    }

    protected function setupSettings(FunctionalTester $I): void
    {
        $I->amOnPage('/setup');
        $I->seeCurrentUrlEquals('/setup/settings');
        $I->seeResponseCodeIs(200);

        $I->seeInTitle('System Settings');

        $this->setupCompleteSettings($I);

        $I->amOnPage('/dashboard');
        $I->seeResponseCodeIs(200);
    }
}
