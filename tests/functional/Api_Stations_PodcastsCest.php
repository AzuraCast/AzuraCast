<?php

class Api_Stations_PodcastsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function managePodcasts(FunctionalTester $I): void
    {
        $I->wantTo('Manage station podcasts via API.');

        $station = $this->getTestStation();

        // Test CRUD for the podcast itself
        $listUrl = '/api/station/' . $station->getId() . '/podcasts';

        $this->testCrudApi(
            $I,
            $listUrl,
            [
                'title' => 'My Awesome Podcast',
                'description' => 'A functional test podcast.',
                'language' => 'en',
            ],
            [
                'title' => 'My Modified Podcast',
                'language' => 'de',
            ]
        );

        // Test CRUD for the episodes
        $I->sendPOST(
            $listUrl,
            [
                'title' => 'Episode Test Podcast',
                'description' => 'A podcast with episodes.',
                'language' => 'en',
            ]
        );
        $I->seeResponseCodeIs(200);

        $newRecordSelfLink = ($I->grabDataFromResponseByJsonPath('links.self'))[0];
        $episodesLink = ($I->grabDataFromResponseByJsonPath('links.episodes'))[0];

        $this->testCrudApi(
            $I,
            $episodesLink,
            [
                'title' => 'My Awesome Podcast Episode',
                'description' => 'A functional test podcast episode!',
                'explicit' => false,
            ],
            [
                'title' => 'My Awesome Suddenly Explicit Podcast Episode',
                'explicit' => true,
            ]
        );

        // Delete Record
        $I->sendDELETE($newRecordSelfLink);

        $I->sendGET($newRecordSelfLink);
        $I->seeResponseCodeIs(404);
    }
}
