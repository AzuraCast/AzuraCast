<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Utilities\Types;
use FunctionalTester;

class Api_Stations_PlaylistsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function managePlaylists(FunctionalTester $I): void
    {
        $I->wantTo('Manage station playlists via API.');

        $station = $this->getTestStation();

        $this->testCrudApi(
            $I,
            '/api/station/' . $station->id . '/playlists',
            [
                'name' => 'General Rotation Playlist',
                'source' => PlaylistSources::Songs->value,
                'type' => PlaylistTypes::Standard->value,
                'weight' => 5,
            ],
            [
                'name' => 'Modified Playlist',
                'type' => PlaylistTypes::Advanced->value,
            ]
        );
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function filterPlaylists(FunctionalTester $I): void
    {
        $I->wantTo('Filter station playlists by source, type and order via API.');

        $station = $this->getTestStation();
        $listUrl = '/api/station/' . $station->id . '/playlists';

        $this->clearPlaylists($I, $listUrl);

        $this->createPlaylist($I, $listUrl, [
            'name' => 'Alpha Songs Shuffle',
            'source' => PlaylistSources::Songs->value,
            'type' => PlaylistTypes::Standard->value,
            'order' => PlaylistOrders::Shuffle->value,
        ]);

        $this->createPlaylist($I, $listUrl, [
            'name' => 'Bravo Songs Hourly',
            'source' => PlaylistSources::Songs->value,
            'type' => PlaylistTypes::OncePerHour->value,
            'order' => PlaylistOrders::Sequential->value,
        ]);

        $this->createPlaylist(
            $I,
            $listUrl,
            [
                'name' => 'Charlie Requests Random',
                'source' => PlaylistSources::Requests->value,
                'order' => PlaylistOrders::Random->value,
            ],
            [
                'name' => 'Charlie Requests Random',
                'source' => PlaylistSources::Requests->value,
                'type' => PlaylistTypes::Standard->value,
                'order' => PlaylistOrders::Random->value,
            ]
        );

        $this->createPlaylist(
            $I,
            $listUrl,
            [
                'name' => 'Delta Remote Random',
                'source' => PlaylistSources::RemoteUrl->value,
                'order' => PlaylistOrders::Random->value,
            ],
            [
                'name' => 'Delta Remote Random',
                'source' => PlaylistSources::RemoteUrl->value,
                'type' => PlaylistTypes::Standard->value,
                'order' => PlaylistOrders::Random->value,
            ]
        );

        $all = [
            'Alpha Songs Shuffle',
            'Bravo Songs Hourly',
            'Charlie Requests Random',
            'Delta Remote Random',
        ];

        $this->seePlaylists($I, $listUrl, [], $all);

        $this->seePlaylists(
            $I,
            $listUrl,
            ['filter' => ['source' => PlaylistSources::Songs->value]],
            ['Alpha Songs Shuffle', 'Bravo Songs Hourly']
        );

        $this->seePlaylists(
            $I,
            $listUrl,
            ['filter' => ['type' => PlaylistTypes::OncePerHour->value]],
            ['Bravo Songs Hourly']
        );

        $this->seePlaylists(
            $I,
            $listUrl,
            ['filter' => ['order' => PlaylistOrders::Random->value]],
            ['Charlie Requests Random', 'Delta Remote Random']
        );

        $this->seePlaylists(
            $I,
            $listUrl,
            [
                'filter' => [
                    'source' => [
                        PlaylistSources::Songs->value,
                        PlaylistSources::Requests->value,
                    ],
                ],
            ],
            ['Alpha Songs Shuffle', 'Bravo Songs Hourly', 'Charlie Requests Random']
        );

        $this->seePlaylists(
            $I,
            $listUrl,
            [
                'filter' => [
                    'source' => PlaylistSources::Songs->value,
                    'type' => PlaylistTypes::Standard->value,
                ],
            ],
            ['Alpha Songs Shuffle']
        );

        $this->seePlaylists(
            $I,
            $listUrl,
            [
                'filter' => [
                    'source' => PlaylistSources::Requests->value,
                    'type' => PlaylistTypes::OncePerHour->value,
                ],
            ],
            []
        );

        $this->seePlaylists($I, $listUrl, ['filter' => ['foo' => 'bar']], $all);
        $this->seePlaylists(
            $I,
            $listUrl,
            [
                'filter' => [
                    'foo' => 'bar',
                    'source' => PlaylistSources::Songs->value,
                ],
            ],
            ['Alpha Songs Shuffle', 'Bravo Songs Hourly']
        );

        $this->seePlaylists($I, $listUrl, ['filter' => ['source' => '']], $all);
        $this->seePlaylists(
            $I,
            $listUrl,
            [
                'filter' => [
                    'source' => PlaylistSources::Songs->value,
                    'type' => '',
                ],
            ],
            ['Alpha Songs Shuffle', 'Bravo Songs Hourly']
        );

        $this->seePlaylists(
            $I,
            $listUrl,
            ['searchPhrase' => 'Random'],
            ['Charlie Requests Random', 'Delta Remote Random']
        );

        $this->seePlaylists(
            $I,
            $listUrl,
            [
                'searchPhrase' => 'Random',
                'filter' => ['source' => PlaylistSources::Requests->value],
            ],
            ['Charlie Requests Random']
        );
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function rejectInvalidPlaylistFilters(FunctionalTester $I): void
    {
        $I->wantTo('Reject playlist filters with values outside their enum.');

        $station = $this->getTestStation();
        $listUrl = '/api/station/' . $station->id . '/playlists';

        $this->seeFilterRejected($I, $listUrl, ['source' => 'bogus']);
        $this->seeFilterRejected($I, $listUrl, ['order' => 'bogus']);
        $this->seeFilterRejected($I, $listUrl, ['type' => PlaylistSources::Songs->value]);
        $this->seeFilterRejected(
            $I,
            $listUrl,
            ['source' => [PlaylistSources::Songs->value, 'bogus']]
        );
    }

    /**
     * @param array<string, mixed> $json
     * @param array<string, mixed>|null $expectedJson
     */
    private function createPlaylist(
        FunctionalTester $I,
        string $listUrl,
        array $json,
        ?array $expectedJson = null
    ): void {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost($listUrl, $json);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson($expectedJson ?? $json);
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string> $expectedNames
     */
    private function seePlaylists(
        FunctionalTester $I,
        string $listUrl,
        array $params,
        array $expectedNames
    ): void {
        ['total' => $total, 'names' => $names] = $this->fetchList($I, $listUrl, $params);

        sort($expectedNames);

        $I->assertSame($expectedNames, $names);
        $I->assertSame(count($expectedNames), $total);
    }

    /**
     * @param array<string, mixed> $filter
     */
    private function seeFilterRejected(FunctionalTester $I, string $listUrl, array $filter): void
    {
        $I->sendGet($listUrl, ['rowCount' => -1, 'filter' => $filter]);

        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'code' => 400,
            'success' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     * @return array{total: int, names: list<string>}
     */
    private function fetchList(FunctionalTester $I, string $listUrl, array $params): array
    {
        $I->sendGet($listUrl, array_merge(['rowCount' => -1], $params));
        $I->seeResponseCodeIs(200);

        $body = Types::array(json_decode($I->grabResponse(), true, 512, JSON_THROW_ON_ERROR));

        $names = [];
        foreach (Types::array($body['rows'] ?? null) as $row) {
            $names[] = Types::string(Types::array($row)['name'] ?? null);
        }

        sort($names);

        return [
            'total' => Types::int($body['total'] ?? null),
            'names' => $names,
        ];
    }

    private function clearPlaylists(FunctionalTester $I, string $listUrl): void
    {
        $I->sendGet($listUrl, ['rowCount' => -1]);
        $I->seeResponseCodeIs(200);

        $body = Types::array(json_decode($I->grabResponse(), true, 512, JSON_THROW_ON_ERROR));

        foreach (Types::array($body['rows'] ?? null) as $row) {
            $links = Types::array(Types::array($row)['links'] ?? null);

            $I->sendDelete(Types::string($links['self'] ?? null));
            $I->seeResponseCodeIs(200);
        }
    }
}
