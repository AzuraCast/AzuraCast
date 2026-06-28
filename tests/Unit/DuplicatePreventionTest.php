<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Api\StationPlaylistQueue;
use App\Radio\AutoDJ\DuplicatePrevention;
use App\Tests\Module;
use Codeception\Test\Unit;
use UnitTester;

class DuplicatePreventionTest extends Unit
{
    protected DuplicatePrevention $duplicatePrevention;

    protected function _inject(Module $testsModule): void
    {
        $di = $testsModule->container;
        $this->duplicatePrevention = $di->get(DuplicatePrevention::class);
    }

    public function testDistinctTracks(): void
    {
        $eligibleTrack = new StationPlaylistQueue();
        $eligibleTrack->artist = 'Foo Fighters feat. AzuraCast Testers';
        $eligibleTrack->title = 'Best of You';
        $eligibleTracks = [$eligibleTrack];

        $fullDuplicateTest = [
            [
                'song_id' => 'best_of_you_foo_fighters',
                'text' => 'Foo Fighters - Best of You',
                'artist' => 'Foo Fighters',
                'title' => 'Best of You',
                'timestamp_played' => 0,
            ],
        ];
        $fullDuplicateResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $fullDuplicateTest);
        $this->assertNull($fullDuplicateResult);

        $artistDuplicateTest = [
            [
                'song_id' => 'everlong_foo_fighters',
                'text' => 'Foo Fighters - Everlong',
                'artist' => 'Foo Fighters',
                'title' => 'Everlong',
                'timestamp_played' => 0,
            ],
        ];
        $artistDuplicateResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $artistDuplicateTest);
        $this->assertNull($artistDuplicateResult);

        $partialDuplicateTest = [
            [
                'song_id' => 'testing_song_foo_fighters_feat_fall_out_boy',
                'text' => 'Foo Fighters feat. Fall Out Boy - Testing Song',
                'artist' => 'Foo Fighters feat. Fall Out Boy',
                'title' => 'Testing Song',
                'timestamp_played' => 0,
            ],
        ];
        $partialDuplicateResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $partialDuplicateTest);
        $this->assertNull($partialDuplicateResult);

        $noDuplicatesTest = [
            [
                'song_id' => 'testing_song_1_panic_at_the_disco',
                'text' => 'Panic! at the Disco - Testing Song 1',
                'artist' => 'Panic! at the Disco',
                'title' => 'Testing Song 1',
                'timestamp_played' => 0,
            ],
            [
                'song_id' => 'lost_memory_sakujo',
                'text' => '削除 - Lost Memory',
                'artist' => '削除',
                'title' => 'Lost Memory',
                'timestamp_played' => 0,
            ],
        ];
        $noDuplicatesResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $noDuplicatesTest);
        $this->assertNotNull($noDuplicatesResult);
    }
}
