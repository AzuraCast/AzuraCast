<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Enums\PodcastSources;
use App\Utilities\Types;
use FunctionalTester;

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
        $listUrl = '/api/station/' . $station->id . '/podcasts';

        $this->testCrudApi(
            $I,
            $listUrl,
            [
                'title' => 'My Awesome Podcast',
                'description' => 'A functional test podcast.',
                'language' => 'en',
                'author' => 'AzuraCast',
                'email' => 'demo@azuracast.com',
                'source' => PodcastSources::Manual->value,
                'playlist_id' => null,
            ],
            [
                'title' => 'My Modified Podcast',
                'language' => 'de',
                'author' => 'Test',
                'email' => 'test@azuracast.com',
            ]
        );

        // Test CRUD for the episodes
        $I->sendPOST(
            $listUrl,
            [
                'title' => 'Episode Test Podcast',
                'description' => 'A podcast with episodes.',
                'language' => 'en',
                'author' => 'AzuraCast',
                'email' => 'demo@azuracast.com',
            ]
        );
        $I->seeResponseCodeIs(200);

        $newRecordSelfLink = ($I->grabDataFromResponseByJsonPath('$.links.self'))[0];
        $episodesLink = ($I->grabDataFromResponseByJsonPath('$.links.episodes'))[0];

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

    /**
     * @before setupComplete
     * @before login
     */
    public function switchPlaylistPodcastToManual(FunctionalTester $I): void
    {
        $I->wantTo('Unlink the playlist when switching a playlist-based podcast to manual via API.');

        $station = $this->getTestStation();

        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPOST(
            '/api/station/' . $station->id . '/playlists',
            [
                'name' => 'Podcast Source Playlist',
                'source' => PlaylistSources::Songs->value,
                'type' => PlaylistTypes::Standard->value,
            ]
        );
        $I->seeResponseCodeIs(200);

        $playlistId = Types::int(($I->grabDataFromResponseByJsonPath('id'))[0]);

        $I->sendPOST(
            '/api/station/' . $station->id . '/podcasts',
            [
                'title' => 'Playlist Podcast',
                'description' => 'A playlist-based podcast.',
                'language' => 'en',
                'author' => 'AzuraCast',
                'email' => 'demo@azuracast.com',
                'source' => PodcastSources::Playlist->value,
                'playlist_id' => $playlistId,
            ]
        );
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['playlist_id' => $playlistId]);

        $podcastSelfLink = ($I->grabDataFromResponseByJsonPath('links.self'))[0];

        // The UI keeps sending the previously selected playlist after switching the source.
        $I->sendPUT(
            $podcastSelfLink,
            [
                'source' => PodcastSources::Manual->value,
                'playlist_id' => $playlistId,
            ]
        );
        $I->seeResponseCodeIs(200);

        $I->sendGET($podcastSelfLink);
        $I->seeResponseContainsJson([
            'source' => PodcastSources::Manual->value,
            'playlist_id' => null,
        ]);
    }
}
