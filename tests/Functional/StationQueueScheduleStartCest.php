<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationQueue;
use App\Entity\StationSchedule;
use App\Radio\AutoDJ\Queue;
use App\Radio\Backend\Liquidsoap\Command\FeedbackCommand;
use App\Radio\Enums\CrossfadeModes;
use Carbon\CarbonImmutable;
use FunctionalTester;

final class StationQueueScheduleStartCest extends CestAbstract
{
    private const string DAY = '2026-10-19';

    private const string SENT_TRACK_EXPECTED_AT = '21:56:00';

    public function _after(FunctionalTester $I): void
    {
        CarbonImmutable::setTestNow();

        parent::_after($I);
    }

    /**
     * @before setupComplete
     */
    public function trackSlidingJustBeforeScheduleStartKeepsItsPlace(FunctionalTester $I): void
    {
        $I->wantTo('Keep a queued track that now starts a few seconds before its playlist is scheduled.');

        $this->seedPlaylists(scheduleStart: 2200, scheduleEnd: 2300);
        $sentTrack = $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '22:00:02');

        $I->assertSame(
            ['21:56:00 Sent Track', '22:00:02 Program 1', '22:10:02 Program 2'],
            $this->upcomingQueue()
        );

        $this->startSentTrack($sentTrack, offsetSeconds: -6);

        $I->assertSame(
            ['21:59:56 Program 1', '22:09:56 Program 2', '22:19:56 Program 3'],
            $this->upcomingQueue()
        );
    }

    /**
     * @before setupComplete
     */
    public function trackSlidingToToleranceLimitKeepsItsPlace(FunctionalTester $I): void
    {
        $I->wantTo('Keep a queued track that now starts exactly 15 seconds before its playlist is scheduled.');

        $this->seedPlaylists(scheduleStart: 2200, scheduleEnd: 2300);
        $sentTrack = $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '22:00:02');

        $this->startSentTrack($sentTrack, offsetSeconds: -17);

        $I->assertSame(
            ['21:59:45 Program 1', '22:09:45 Program 2', '22:19:45 Program 3'],
            $this->upcomingQueue()
        );
    }

    /**
     * @before setupComplete
     */
    public function trackSlidingPastToleranceLimitIsReplaced(FunctionalTester $I): void
    {
        $I->wantTo('Replace a queued track that now starts 16 seconds before its playlist is scheduled.');

        $this->seedPlaylists(scheduleStart: 2200, scheduleEnd: 2300);
        $sentTrack = $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '22:00:02');

        $this->startSentTrack($sentTrack, offsetSeconds: -18);

        $I->assertSame(
            ['21:59:44 Filler 1', '22:03:44 Program 1', '22:13:44 Program 2'],
            $this->upcomingQueue()
        );
    }

    /**
     * @before setupComplete
     */
    public function trackSlidingPastScheduleEndIsReplaced(FunctionalTester $I): void
    {
        $I->wantTo('Replace a queued track that now starts after its playlist schedule has ended.');

        $this->seedPlaylists(scheduleStart: 2100, scheduleEnd: 2200);
        $sentTrack = $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '21:59:58');

        $this->startSentTrack($sentTrack, offsetSeconds: 6);

        $I->assertSame(
            ['22:00:04 Filler 1', '22:04:04 Filler 2', '22:08:04 Filler 3'],
            $this->upcomingQueue()
        );
    }

    /**
     * @before setupComplete
     */
    public function pickJustBeforeScheduleStartPlaysProgram(FunctionalTester $I): void
    {
        $I->wantTo('Pick the program for a track expected a few seconds before its playlist is scheduled.');

        $this->seedPlaylists(scheduleStart: 2200, scheduleEnd: 2300);
        $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '21:59:56');

        $I->assertSame(
            ['21:56:00 Sent Track', '21:59:56 Program 1', '22:09:56 Program 2'],
            $this->upcomingQueue()
        );
    }

    /**
     * @before setupComplete
     */
    public function pickPastToleranceLimitPlaysFiller(FunctionalTester $I): void
    {
        $I->wantTo('Pick a filler for a track expected 16 seconds before the program is scheduled.');

        $this->seedPlaylists(scheduleStart: 2200, scheduleEnd: 2300);
        $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '21:59:44');

        $I->assertSame(
            ['21:56:00 Sent Track', '21:59:44 Filler 1', '22:03:44 Program 1'],
            $this->upcomingQueue()
        );
    }

    /**
     * @before setupComplete
     */
    public function trackOfEndingScheduleIsReplacedWhenNextScheduleStarts(FunctionalTester $I): void
    {
        $I->wantTo('Replace a queued track of an ending schedule when it now starts just before the next schedule.');

        $this->seedBackToBackPrograms();
        $sentTrack = $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '21:59:40');

        $I->assertSame(
            ['21:56:00 Sent Track', '21:59:40 Early Program 1', '22:09:40 Late Program 1'],
            $this->upcomingQueue()
        );

        $this->startSentTrack($sentTrack, offsetSeconds: 16);

        $I->assertSame(
            ['21:59:56 Late Program 1', '22:09:56 Late Program 2', '22:19:56 Late Program 3'],
            $this->upcomingQueue()
        );
    }

    /**
     * @before setupComplete
     */
    public function loopOnceShowStartingJustBeforeScheduleStartPlaysOnce(FunctionalTester $I): void
    {
        $I->wantTo('Play a loop once show only once when it starts a few seconds before its schedule.');

        $this->seedRecordedShow();
        $sentTrack = $this->buildQueueAfterSentTrack(nextTrackExpectedAt: '22:00:02');

        $this->startSentTrack($sentTrack, offsetSeconds: -6);

        $I->assertSame(
            ['21:59:56 Recorded Show', '22:29:56 Filler 1', '22:33:56 Filler 2'],
            $this->upcomingQueue()
        );

        $this->startNextQueuedTrack(startedAt: '21:59:56');

        $I->assertSame(
            ['22:29:56 Filler 1', '22:33:56 Filler 2', '22:37:56 Filler 3'],
            $this->upcomingQueue()
        );
    }

    private function seedPlaylists(int $scheduleStart, int $scheduleEnd): void
    {
        $this->disableCrossfade();
        $this->seedGeneralPlaylist();
        $this->seedProgram('Program', $scheduleStart, $scheduleEnd);

        $this->em->flush();
        $this->em->clear();
    }

    private function seedBackToBackPrograms(): void
    {
        $this->disableCrossfade();
        $this->seedGeneralPlaylist();
        $this->seedProgram('Early Program', 2100, 2200);
        $this->seedProgram('Late Program', 2200, 2300);

        $this->em->flush();
        $this->em->clear();
    }

    private function seedRecordedShow(): void
    {
        $this->disableCrossfade();
        $this->seedGeneralPlaylist();

        $show = $this->seedPlaylist('Recorded Show');
        $this->addMedia($show, 'Recorded Show', 1800);

        $schedule = $this->addSchedule($show, 2200, 2300);
        $schedule->loop_once = true;
        $schedule->reset_queue_at_start = true;

        $this->em->flush();
        $this->em->clear();
    }

    private function buildQueueAfterSentTrack(string $nextTrackExpectedAt): StationQueue
    {
        CarbonImmutable::setTestNow($this->at('21:52:00'));

        $currentTrack = $this->uploadMedia('Current Track', 240);
        $this->sendFeedback(['media_id' => (string) $currentTrack->id]);

        $sentTrackLength = $this->at(self::SENT_TRACK_EXPECTED_AT)->diffInSeconds($this->at($nextTrackExpectedAt));
        $sentTrackMedia = $this->uploadMedia('Sent Track', $sentTrackLength);

        $sentTrack = StationQueue::fromMedia($this->getTestStation(), $sentTrackMedia);
        $sentTrack->sent_to_autodj = true;

        $this->em->persist($sentTrack);
        $this->em->flush();
        $this->em->clear();

        $this->buildQueue();

        return $sentTrack;
    }

    /**
     * @return string[]
     */
    private function upcomingQueue(): array
    {
        $station = $this->getTestStation();
        $timezone = $station->getTimezoneObject();

        $upcomingQueue = [];
        foreach ($this->di->get(StationQueueRepository::class)->getUnplayedQueue($station) as $row) {
            $expectedPlayTime = $row->timestamp_played?->setTimezone($timezone)->format('H:i:s');
            $upcomingQueue[] = "{$expectedPlayTime} {$row->title}";
        }

        return $upcomingQueue;
    }

    private function startSentTrack(StationQueue $sentTrack, int $offsetSeconds): void
    {
        $this->startTrack($sentTrack, $this->at(self::SENT_TRACK_EXPECTED_AT)->addSeconds($offsetSeconds));
    }

    private function startNextQueuedTrack(string $startedAt): void
    {
        $nextTrack = $this->di->get(StationQueueRepository::class)->getUnplayedQueue($this->getTestStation())[0];

        $this->startTrack($nextTrack, $this->at($startedAt));
    }

    private function startTrack(StationQueue $track, CarbonImmutable $startedAt): void
    {
        CarbonImmutable::setTestNow($startedAt);

        $this->sendFeedback([
            'media_id' => (string) $track->media?->id,
            'sq_id' => (string) $track->id,
        ]);

        $this->buildQueue();
    }

    private function disableCrossfade(): void
    {
        $station = $this->getTestStation();

        $backendConfig = $station->backend_config;
        $backendConfig->crossfade_type = CrossfadeModes::Disabled->value;
        $station->backend_config = $backendConfig;

        $this->em->persist($station);
        $this->em->flush();
    }

    private function seedGeneralPlaylist(): void
    {
        $general = $this->seedPlaylist('General');
        foreach (range(1, 3) as $number) {
            $this->addMedia($general, "Filler {$number}", 240);
        }
    }

    private function seedProgram(string $name, int $scheduleStart, int $scheduleEnd): void
    {
        $program = $this->seedPlaylist($name);
        foreach (range(1, 3) as $number) {
            $this->addMedia($program, "{$name} {$number}", 600);
        }

        $this->addSchedule($program, $scheduleStart, $scheduleEnd);
    }

    private function seedPlaylist(string $name): StationPlaylist
    {
        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = $name;
        $playlist->source = PlaylistSources::Songs;
        $playlist->order = PlaylistOrders::Sequential;

        $this->em->persist($playlist);
        $this->em->flush();

        return $playlist;
    }

    private function addMedia(StationPlaylist $playlist, string $title, float $length): void
    {
        $media = $this->uploadMedia($title, $length);

        $this->di->get(StationPlaylistMediaRepository::class)->addMediaToPlaylist($media, $playlist);
        $this->em->flush();
    }

    private function addSchedule(StationPlaylist $playlist, int $startTime, int $endTime): StationSchedule
    {
        $schedule = new StationSchedule($playlist);
        $schedule->start_time = $startTime;
        $schedule->end_time = $endTime;

        $this->em->persist($schedule);

        return $schedule;
    }

    private function uploadMedia(string $title, float $length): StationMedia
    {
        $fileName = strtolower(str_replace(' ', '-', $title));

        $media = $this->uploadTestSong("{$fileName}.mp3");
        $media->artist = $title;
        $media->title = $title;
        $media->text = null;
        $media->updateMetaFields();
        $media->length = $length;

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    private function sendFeedback(array $payload): void
    {
        $this->di->get(FeedbackCommand::class)->run($this->getTestStation(), true, $payload);
        $this->em->clear();
    }

    private function buildQueue(): void
    {
        $this->di->get(Queue::class)->buildQueue($this->getTestStation());
        $this->em->clear();
    }

    private function at(string $time): CarbonImmutable
    {
        $day = self::DAY;

        return CarbonImmutable::parse("{$day} {$time}", $this->getTestStation()->timezone);
    }
}
