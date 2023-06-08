<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_Frontend_AccountCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function checkAccount(FunctionalTester $I): void
    {
        $I->wantTo('Check frontend account API functions.');

        // GET me endpoint
        $I->sendGet('/api/frontend/account/me');
        $I->seeResponseCodeIsSuccessful();

        $I->seeResponseContainsJson(
            [
                'email' => $this->login_username,
                'name'  => 'AzuraCast Test User',
            ]
        );

        // PUT me endpoint
        $I->sendPut('/api/frontend/account/me', [
            'name' => 'AzuraCast User with New Name',
        ]);
        $I->seeResponseCodeIsSuccessful();

        $I->sendGet('/api/frontend/account/me');
        $I->seeResponseContainsJson([
            'name' => 'AzuraCast User with New Name',
        ]);

        // PUT password endpoint
        $I->sendPut('/api/frontend/account/password', [
            'current_password' => 'wrongpassword',
            'new_password'     => 'asdfasdfasdfasdf',
        ]);
        $I->seeResponseCodeIs(400);

        // GET twofactor endpoint
        $I->sendGet('/api/frontend/account/two-factor');
        $I->seeResponseCodeIsSuccessful();

        $I->seeResponseContainsJson([
            'two_factor_enabled' => false,
        ]);

        // PUT twofactor endpoint without secret
        $I->sendPut('/api/frontend/account/two-factor');
        $I->seeResponseCodeIsSuccessful();

        // CRUD API Keys
        $createJson = [
            'comment' => 'API Key Test',
        ];

        $I->sendPost('/api/frontend/account/api-keys', $createJson);
        $I->seeResponseCodeIsSuccessful();

        $newRecord = $I->grabDataFromResponseByJsonPath('links.self');
        $newRecordSelfLink = $newRecord[0];

        $I->sendGet($newRecordSelfLink);
        $I->seeResponseContainsJson($createJson);

        $I->sendGet($newRecordSelfLink);
        $I->seeResponseContainsJson($createJson);

        $I->sendDelete($newRecordSelfLink);

        $I->sendGet($newRecordSelfLink);
        $I->seeResponseCodeIs(404);
    }
}
