<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_Stations_StreamersCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageStreamers(FunctionalTester $I): void
    {
        $I->wantTo('Manage station streamers via API.');

        // Create new record
        $station = $this->getTestStation();
        $station->setEnableStreamers(true);
        $this->em->persist($station);
        $this->em->flush();

        $listUrl = '/api/station/' . $station->getId() . '/streamers';

        $I->sendPOST(
            $listUrl,
            [
                'streamer_username' => 'test',
                'streamer_password' => 'test',
                'display_name' => 'Test Streamer',
            ]
        );

        $I->seeResponseCodeIs(200);

        $newRecord = $I->grabDataFromResponseByJsonPath('links.self');
        $newRecordSelfLink = $newRecord[0];

        // Get single record.
        $I->sendGET($newRecordSelfLink);

        $I->seeResponseContainsJson(
            [
                'streamer_username' => 'test',
                'display_name' => 'Test Streamer',
            ]
        );

        // Modify record.
        $editJson = [
            'display_name' => 'Different Test Streamer',
        ];

        $I->sendPUT($newRecordSelfLink, $editJson);

        // List all records.
        $I->sendGET($newRecordSelfLink);

        $I->seeResponseContainsJson($editJson);

        // Delete Record
        $I->sendDELETE($newRecordSelfLink);

        $I->sendGET($newRecordSelfLink);
        $I->seeResponseCodeIs(404);
    }
}
