<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistGroup;
use App\Entity\StationPlaylistMedia;
use App\Radio\Configuration;
use Carbon\CarbonImmutable;
use FunctionalTester;
use RuntimeException;

final class PlaylistQueueResetOnRestartCest extends CestAbstract
{
    private const string PREVIOUS_QUEUE_RESET_AT = '2020-01-01 00:00:00';

    /**
     * @before setupComplete
     */
    public function preservesSequentialByDefault(FunctionalTester $I): void
    {
        $I->wantTo('Keep sequential playlist and group queues on restart by default.');

        $media = $this->uploadTestSong();
        $sequential = $this->seedPlaylist($media, 'Sequential', PlaylistOrders::Sequential, false);
        $shuffle = $this->seedPlaylist($media, 'Shuffle', PlaylistOrders::Shuffle, false);
        $random = $this->seedPlaylist($media, 'Random', PlaylistOrders::Random, false);
        $sequentialGroup = $this->seedGroup($shuffle, 'Sequential Group', PlaylistOrders::Sequential, false);
        $shuffleGroup = $this->seedGroup($shuffle, 'Shuffle Group', PlaylistOrders::Shuffle, false);

        $now = $this->initializeConfiguration();

        $this->assertQueuePreserved($I, $sequential);
        $this->assertQueueReset($I, $shuffle, $now);
        $this->assertQueueResetAt($I, $random, $now);
        $this->assertGroupQueuePreserved($I, $sequentialGroup);
        $this->assertGroupQueueReset($I, $shuffleGroup, $now);
    }

    /**
     * @before setupComplete
     */
    public function playlistFlagPreservesShuffle(FunctionalTester $I): void
    {
        $I->wantTo('Keep a shuffle playlist queue when only that playlist opts out.');

        $media = $this->uploadTestSong();
        $preserved = $this->seedPlaylist($media, 'Preserved Shuffle', PlaylistOrders::Shuffle, true);
        $reset = $this->seedPlaylist($media, 'Shuffle', PlaylistOrders::Shuffle, false);
        $preservedGroup = $this->seedGroup($reset, 'Preserved Shuffle Group', PlaylistOrders::Shuffle, true);
        $resetGroup = $this->seedGroup($reset, 'Shuffle Group', PlaylistOrders::Shuffle, false);

        $now = $this->initializeConfiguration();

        $this->assertQueuePreserved($I, $preserved);
        $this->assertQueueReset($I, $reset, $now);
        $this->assertGroupQueuePreserved($I, $preservedGroup);
        $this->assertGroupQueueReset($I, $resetGroup, $now);
    }

    /**
     * @before setupComplete
     */
    public function stationFlagResetsSequentialUnlessPlaylistOptsOut(FunctionalTester $I): void
    {
        $I->wantTo('Reset sequential queues when the station flag is set, except for playlists that opt out.');

        $this->setResetSequentialFlag(true);

        $media = $this->uploadTestSong();
        $sequential = $this->seedPlaylist($media, 'Sequential', PlaylistOrders::Sequential, false);
        $sequentialPreserved = $this->seedPlaylist($media, 'Preserved Sequential', PlaylistOrders::Sequential, true);
        $shufflePreserved = $this->seedPlaylist($media, 'Preserved Shuffle', PlaylistOrders::Shuffle, true);
        $shuffle = $this->seedPlaylist($media, 'Shuffle', PlaylistOrders::Shuffle, false);
        $sequentialGroup = $this->seedGroup($shuffle, 'Sequential Group', PlaylistOrders::Sequential, false);
        $sequentialGroupPreserved = $this->seedGroup(
            $shuffle,
            'Preserved Sequential Group',
            PlaylistOrders::Sequential,
            true
        );
        $shuffleGroupPreserved = $this->seedGroup($shuffle, 'Preserved Shuffle Group', PlaylistOrders::Shuffle, true);
        $shuffleGroup = $this->seedGroup($shuffle, 'Shuffle Group', PlaylistOrders::Shuffle, false);

        $now = $this->initializeConfiguration();

        $this->assertQueueReset($I, $sequential, $now);
        $this->assertQueuePreserved($I, $sequentialPreserved);
        $this->assertQueuePreserved($I, $shufflePreserved);
        $this->assertQueueReset($I, $shuffle, $now);
        $this->assertGroupQueueReset($I, $sequentialGroup, $now);
        $this->assertGroupQueuePreserved($I, $sequentialGroupPreserved);
        $this->assertGroupQueuePreserved($I, $shuffleGroupPreserved);
        $this->assertGroupQueueReset($I, $shuffleGroup, $now);
    }

    private function setResetSequentialFlag(bool $value): void
    {
        $station = $this->getTestStation();

        $backendConfig = $station->backend_config;
        $backendConfig->reset_sequential_queues_on_restart = $value;
        $station->backend_config = $backendConfig;

        $this->em->persist($station);
        $this->em->flush();
    }

    private function seedPlaylist(
        StationMedia $media,
        string $name,
        PlaylistOrders $order,
        bool $preserveQueueOnRestart
    ): StationPlaylist {
        $station = $this->getTestStation();

        $playlist = new StationPlaylist($station);
        $playlist->name = $name;
        $playlist->source = PlaylistSources::Songs;
        $playlist->order = $order;
        $playlist->preserve_queue_on_restart = $preserveQueueOnRestart;
        $playlist->queue_reset_at = CarbonImmutable::parse(self::PREVIOUS_QUEUE_RESET_AT, 'UTC');

        $this->em->persist($playlist);
        $this->em->flush();

        $spmRepo = $this->di->get(StationPlaylistMediaRepository::class);
        $spmRepo->addMediaToPlaylist($media, $playlist);
        $this->em->flush();

        $spm = $this->findPlaylistMedia($playlist);
        $spm->is_queued = false;

        $this->em->persist($spm);
        $this->em->flush();

        return $playlist;
    }

    private function seedGroup(
        StationPlaylist $member,
        string $name,
        PlaylistOrders $order,
        bool $preserveQueueOnRestart
    ): StationPlaylist {
        $station = $this->getTestStation();

        $group = new StationPlaylist($station);
        $group->name = $name;
        $group->source = PlaylistSources::Playlists;
        $group->order = $order;
        $group->preserve_queue_on_restart = $preserveQueueOnRestart;
        $group->queue_reset_at = CarbonImmutable::parse(self::PREVIOUS_QUEUE_RESET_AT, 'UTC');

        $spg = new StationPlaylistGroup($member, $group);
        $spg->is_queued = false;

        $this->em->persist($group);
        $this->em->persist($spg);
        $this->em->flush();

        return $group;
    }

    private function initializeConfiguration(): CarbonImmutable
    {
        $now = CarbonImmutable::parse('2026-09-06 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        try {
            $this->em->clear();
            $station = $this->getTestStation();

            $this->di->get(Configuration::class)->initializeConfiguration($station);
        } finally {
            CarbonImmutable::setTestNow();
        }

        $this->em->clear();

        return $now;
    }

    private function assertQueueReset(
        FunctionalTester $I,
        StationPlaylist $playlist,
        CarbonImmutable $now
    ): void {
        $playlist = $this->em->refetch($playlist);

        $I->assertTrue(
            $this->findPlaylistMedia($playlist)->is_queued,
            sprintf('Playlist "%s" should have its media re-queued.', $playlist->name)
        );
        $this->assertQueueResetAt($I, $playlist, $now);
    }

    private function assertQueueResetAt(
        FunctionalTester $I,
        StationPlaylist $playlist,
        CarbonImmutable $now
    ): void {
        $playlist = $this->em->refetch($playlist);

        $I->assertSame(
            $now->getTimestamp(),
            $playlist->queue_reset_at?->getTimestamp(),
            sprintf('Playlist "%s" should have its queue_reset_at stamped.', $playlist->name)
        );
    }

    private function assertQueuePreserved(FunctionalTester $I, StationPlaylist $playlist): void
    {
        $playlist = $this->em->refetch($playlist);

        $I->assertFalse(
            $this->findPlaylistMedia($playlist)->is_queued,
            sprintf('Playlist "%s" should keep its consumed media.', $playlist->name)
        );
        $I->assertSame(
            CarbonImmutable::parse(self::PREVIOUS_QUEUE_RESET_AT, 'UTC')->getTimestamp(),
            $playlist->queue_reset_at?->getTimestamp(),
            sprintf('Playlist "%s" should keep its previous queue_reset_at.', $playlist->name)
        );
    }

    private function assertGroupQueueReset(
        FunctionalTester $I,
        StationPlaylist $group,
        CarbonImmutable $now
    ): void {
        $group = $this->em->refetch($group);

        $I->assertTrue(
            $this->findGroupMember($group)->is_queued,
            sprintf('Group "%s" should have its members re-queued.', $group->name)
        );
        $this->assertQueueResetAt($I, $group, $now);
    }

    private function assertGroupQueuePreserved(FunctionalTester $I, StationPlaylist $group): void
    {
        $group = $this->em->refetch($group);

        $I->assertFalse(
            $this->findGroupMember($group)->is_queued,
            sprintf('Group "%s" should keep its consumed members.', $group->name)
        );
        $I->assertSame(
            CarbonImmutable::parse(self::PREVIOUS_QUEUE_RESET_AT, 'UTC')->getTimestamp(),
            $group->queue_reset_at?->getTimestamp(),
            sprintf('Group "%s" should keep its previous queue_reset_at.', $group->name)
        );
    }

    private function findGroupMember(StationPlaylist $group): StationPlaylistGroup
    {
        $spg = $this->em->getRepository(StationPlaylistGroup::class)->findOneBy(['playlist_group' => $group]);

        if (!($spg instanceof StationPlaylistGroup)) {
            throw new RuntimeException(sprintf('No member found for group "%s".', $group->name));
        }

        return $spg;
    }

    private function findPlaylistMedia(StationPlaylist $playlist): StationPlaylistMedia
    {
        $spm = $this->em->getRepository(StationPlaylistMedia::class)->findOneBy(['playlist' => $playlist]);

        if (!($spm instanceof StationPlaylistMedia)) {
            throw new RuntimeException(sprintf('No media found for playlist "%s".', $playlist->name));
        }

        return $spm;
    }
}
