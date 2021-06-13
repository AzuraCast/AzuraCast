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

        $this->testCrudApi(
            $I,
            '/api/station/' . $station->getId() . '/podcasts',
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
    }
}
