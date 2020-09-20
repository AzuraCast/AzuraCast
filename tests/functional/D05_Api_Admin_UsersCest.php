<?php

class D05_Api_Admin_UsersCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageUsers(FunctionalTester $I): void
    {
        $I->wantTo('Manage users via API.');

        // Create new record
        $I->sendPOST('/api/admin/users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $I->seeResponseCodeIs(200);

        $newRecord = $I->grabDataFromResponseByJsonPath('id');
        $newRecordId = $newRecord[0];

        // Get single record.
        $I->sendGET('/api/admin/user/' . $newRecordId);

        $I->seeResponseContainsJson([
            'id' => $newRecordId,
            'name' => 'Test User',
        ]);

        // Modify record.
        $I->sendPUT('/api/admin/user/' . $newRecordId, [
            'name' => 'Test User Renamed',
        ]);

        // List all records.
        $I->sendGET('/api/admin/user/' . $newRecordId);

        $I->seeResponseContainsJson([
            'id' => $newRecordId,
            'name' => 'Test User Renamed',
            'email' => 'test@example.com',
        ]);

        // Delete Record
        $I->sendDELETE('/api/admin/user/' . $newRecordId);

        $I->sendGET('/api/admin/user/' . $newRecordId);
        $I->seeResponseCodeIs(404);
    }
}
