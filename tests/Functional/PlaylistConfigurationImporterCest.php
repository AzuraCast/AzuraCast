<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\StationPlaylistGroup;
use App\Service\PlaylistConfiguration\PlaylistConfigurationImporter;
use FunctionalTester;

final class PlaylistConfigurationImporterCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function duplicateMemberRowsSharePreserveQueueOnRecursiveReset(FunctionalTester $I): void
    {
        $I->wantTo('Exempt every row of a duplicated member from nested resets when one of its rows is exempt.');

        $importer = $this->di->get(PlaylistConfigurationImporter::class);

        $summary = $importer->import(
            [
                'schema_version' => 1,
                'type' => 'station',
                'playlists' => [
                    $this->songsPlaylist('songs', 'Songs'),
                    $this->songsPlaylist('other_songs', 'Other Songs'),
                    [
                        'ref' => 'group',
                        'name' => 'Group',
                        'config' => [
                            'type' => 'default',
                            'source' => 'playlists',
                            'order' => 'shuffle',
                        ],
                        'members' => [
                            [
                                'playlist_ref' => 'songs',
                                'weight' => 1,
                            ],
                            [
                                'playlist_ref' => 'songs',
                                'weight' => 2,
                                'preserve_queue_on_recursive_reset' => true,
                            ],
                            [
                                'playlist_ref' => 'other_songs',
                                'weight' => 3,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getTestStation()
        );

        $groupId = $summary->playlistsByRef['group']->id;

        $this->em->clear();

        $memberRows = $this->em->getRepository(StationPlaylistGroup::class)->findBy(
            ['playlist_group' => $groupId],
            ['weight' => 'ASC']
        );

        $I->assertSame(
            [true, true, false],
            array_map(
                static fn(StationPlaylistGroup $memberRow): bool => $memberRow->preserve_queue_on_recursive_reset,
                $memberRows
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function songsPlaylist(string $ref, string $name): array
    {
        return [
            'ref' => $ref,
            'name' => $name,
            'config' => [
                'type' => 'default',
                'source' => 'songs',
                'order' => 'shuffle',
            ],
        ];
    }
}
