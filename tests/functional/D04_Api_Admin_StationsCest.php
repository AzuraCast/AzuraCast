<?php

class D04_Api_Admin_StationsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageStations(FunctionalTester $I): void
    {
        $I->wantTo('Manage stations via API.');

        // Create new record
        $I->sendPOST('/api/admin/stations', [
            'name' => 'Test Station',
        ]);

        $I->seeResponseCodeIs(200);

        $newRecord = $I->grabDataFromResponseByJsonPath('id');
        $newRecordId = $newRecord[0];

        // Get single record.
        $I->sendGET('/api/admin/station/' . $newRecordId);

        $I->seeResponseContainsJson([
            'id' => $newRecordId,
            'name' => 'Test Station',
            'short_name' => 'test_station',
        ]);

        // Modify record.
        $I->sendPUT('/api/admin/station/' . $newRecordId, [
            'name' => 'Test Station Renamed',
        ]);

        // List all records.
        $I->sendGET('/api/admin/station/' . $newRecordId);

        $I->seeResponseContainsJson([
            'id' => $newRecordId,
            'name' => 'Test Station Renamed',
            'short_name' => 'test_station',
        ]);

        // Delete Record
        $I->sendDELETE('/api/admin/station/' . $newRecordId);

        $I->sendGET('/api/admin/station/' . $newRecordId);
        $I->seeResponseCodeIs(404);
    }
}
