<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Api\StationPlaylistQueue;
use App\Radio\AutoDJ\DuplicatePrevention;
use App\Radio\AutoDJ\RecentSongHistory;
use App\Tests\Module;
use Codeception\Test\Unit;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
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

    public function testArtistOutsideArtistTimeRangeIsDistinct(): void
    {
        $eligibleTracks = [$this->eligibleTrack(1, 'Foo Fighters feat. AzuraCast Testers', 'Best of You')];
        $playedTracks = [$this->playedTrack('Foo Fighters', 'Everlong')];

        $this->assertNotNull($this->duplicatePrevention->getDistinctTrack($eligibleTracks, $playedTracks, []));
        $this->assertNull($this->duplicatePrevention->getDistinctTrack($eligibleTracks, $playedTracks, $playedTracks));
    }

    public function testTitleInsideTimeRangeStillBlocks(): void
    {
        $eligibleTracks = [$this->eligibleTrack(1, 'Foo Fighters', 'Best of You')];
        $playedTracks = [$this->playedTrack('Other Artist', 'Best of You')];

        $this->assertNull($this->duplicatePrevention->getDistinctTrack($eligibleTracks, $playedTracks, []));
    }

    public function testMissingArtistPlayedTracksUsesPlayedTracks(): void
    {
        $eligibleTracks = [$this->eligibleTrack(1, 'Foo Fighters feat. AzuraCast Testers', 'Best of You')];

        $playedTrackLists = [
            [$this->playedTrack('Foo Fighters', 'Best of You')],
            [$this->playedTrack('Foo Fighters', 'Everlong')],
            [$this->playedTrack('Foo Fighters feat. Fall Out Boy', 'Testing Song')],
            [$this->playedTrack('Panic! at the Disco', 'Testing Song 1')],
        ];

        foreach ($playedTrackLists as $playedTracks) {
            $expected = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $playedTracks);

            $this->assertSame(
                $expected,
                $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $playedTracks, null)
            );
            $this->assertSame(
                $expected,
                $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $playedTracks, $playedTracks)
            );
        }
    }

    public function testPreventDuplicatesPicksFirstTrackPassingArtistTimeRange(): void
    {
        $sameArtistTrack = $this->eligibleTrack(1, 'Foo Fighters', 'Best of You');
        $otherArtistTrack = $this->eligibleTrack(2, 'Panic! at the Disco', 'Testing Song');
        $playedTracks = [$this->playedTrack('Foo Fighters', 'Everlong')];

        $this->assertSame(
            $sameArtistTrack,
            $this->duplicatePrevention->preventDuplicates(
                [$sameArtistTrack, $otherArtistTrack],
                new RecentSongHistory($playedTracks, [])
            )
        );
        $this->assertSame(
            $otherArtistTrack,
            $this->duplicatePrevention->preventDuplicates(
                [$sameArtistTrack, $otherArtistTrack],
                new RecentSongHistory($playedTracks, $playedTracks)
            )
        );
    }

    public function testNoDistinctTrackLogsSameArtistWhenOnlyArtistsRepeat(): void
    {
        $eligibleTracks = [$this->eligibleTrack(1, 'Foo Fighters', 'Best of You')];
        $playedTracks = [$this->playedTrack('Foo Fighters', 'Everlong')];

        $this->assertNoDistinctTrackLogs(
            $eligibleTracks,
            new RecentSongHistory($playedTracks, $playedTracks),
            'No track avoids same artist.',
            'No way to avoid same artist; using least recently played song.'
        );
    }

    public function testNoDistinctTrackLogsSameTitleWhenEveryTitleRepeats(): void
    {
        $eligibleTracks = [$this->eligibleTrack(1, 'Foo Fighters', 'Best of You')];
        $playedTracks = [$this->playedTrack('Other Artist', 'Best of You')];

        $this->assertNoDistinctTrackLogs(
            $eligibleTracks,
            new RecentSongHistory($playedTracks, $playedTracks),
            'No track avoids same title.',
            'No way to avoid same title; using least recently played song.'
        );
    }

    /**
     * @param StationPlaylistQueue[] $eligibleTracks
     */
    private function assertNoDistinctTrackLogs(
        array $eligibleTracks,
        RecentSongHistory $recentSongHistory,
        string $strictMessage,
        string $leastRecentlyPlayedMessage
    ): void {
        $logHandler = new TestHandler();

        $duplicatePrevention = new DuplicatePrevention();
        $duplicatePrevention->setLogger(new Logger('test_duplicate_prevention', [$logHandler]));

        $this->assertNull($duplicatePrevention->preventDuplicates($eligibleTracks, $recentSongHistory));
        $this->assertTrue($logHandler->hasDebugThatContains($strictMessage));

        $this->assertSame(
            $eligibleTracks[0],
            $duplicatePrevention->preventDuplicates($eligibleTracks, $recentSongHistory, true)
        );
        $this->assertTrue($logHandler->hasWarningThatContains($leastRecentlyPlayedMessage));
    }

    private function eligibleTrack(int $mediaId, string $artist, string $title): StationPlaylistQueue
    {
        $eligibleTrack = new StationPlaylistQueue();
        $eligibleTrack->spm_id = $mediaId;
        $eligibleTrack->media_id = $mediaId;
        $eligibleTrack->song_id = "eligible_{$mediaId}";
        $eligibleTrack->artist = $artist;
        $eligibleTrack->title = $title;
        $eligibleTrack->last_played = null;

        return $eligibleTrack;
    }

    /**
     * @return array{
     *  song_id: string,
     *  text: string,
     *  artist: string,
     *  title: string,
     *  timestamp_played: int
     * }
     */
    private function playedTrack(string $artist, string $title): array
    {
        return [
            'song_id' => "played_{$artist}_{$title}",
            'text' => "{$artist} - {$title}",
            'artist' => $artist,
            'title' => $title,
            'timestamp_played' => 0,
        ];
    }
}
