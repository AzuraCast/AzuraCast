<?php

class B01_Admin_ApiKeysCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageApiKeys(FunctionalTester $I): void
    {
        $I->wantTo('Create and administer API keys.');

        // Create one API key and test its revocation within the user side.
        $I->amOnPage('/api_keys');
        $I->see('My API Keys');

        $I->click('add', '#content');

        $I->submitForm('.form', [
            'comment' => 'API Key Test',
        ]);

        $I->seeCurrentUrlEquals('/api_keys/add');
        $I->see('New Key Generated');

        $I->click('.btn-primary'); // Continue

        $I->amOnPage('/api_keys');
        $I->see('API Key Test');

        $I->click(\Codeception\Util\Locator::lastElement('.btn-danger')); // Revoke

        $I->seeCurrentUrlEquals('/api_keys');
        $I->dontSee('API Key Test');

        // Create another API key and test its revocation from the admin side.
        $I->click('add', '#content');

        $I->submitForm('.form', [
            'comment' => 'API Key Admin Test',
        ]);

        $I->amOnPage('/admin/api');
        $I->see('API Key Admin Test');

        $I->click(\Codeception\Util\Locator::lastElement('.btn-danger'));

        $I->seeCurrentUrlEquals('/admin/api');
        $I->dontSee('API Key Admin Test');
    }
}
