<?php

class D03_Api_Admin_CustomFieldsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageCustomFields(FunctionalTester $I): void
    {
        $I->wantTo('Manage custom fields via API.');

        // Create new record
        $I->sendPOST('/api/admin/custom_fields', [
            'name' => 'Test Field',
        ]);

        $I->seeResponseCodeIs(200);

        $newRecord = $I->grabDataFromResponseByJsonPath('id');
        $newRecordId = $newRecord[0];

        // Get single record.
        $I->sendGET('/api/admin/custom_field/' . $newRecordId);

        $I->seeResponseContainsJson([
            'id' => $newRecordId,
            'name' => 'Test Field',
        ]);

        // Modify record.
        $I->sendPUT('/api/admin/custom_field/' . $newRecordId, [
            'name' => 'Test Field Renamed',
        ]);

        // List all records.
        $I->sendGET('/api/admin/custom_field/' . $newRecordId);

        $I->seeResponseContainsJson([
            'id' => $newRecordId,
            'name' => 'Test Field Renamed',
        ]);

        // Delete Record
        $I->sendDELETE('/api/admin/custom_field/' . $newRecordId);

        $I->sendGET('/api/admin/custom_field/' . $newRecordId);
        $I->seeResponseCodeIs(404);
    }
}
