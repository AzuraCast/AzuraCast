<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Radio\AutoDJ\Queue;
use App\Radio\Configuration;
use Carbon\CarbonImmutable;
use FunctionalTester;
use RuntimeException;

final class StationQueueSlotRestoreCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function clearUnplayedRestoresConsumedSlots(FunctionalTester $I): void
    {
        $I->wantTo('Hand the rotation slot back for every unplayed queue row that gets cleared.');

        $playlist = $this->seedSongsPlaylist('Sequential', PlaylistOrders::Sequential, false);
        $sent = $this->addMedia($playlist, 'sent.mp3');
        $unsent = $this->addMedia($playlist, 'unsent.mp3');
        $played = $this->addMedia($playlist, 'played.mp3');

        $this->cue($playlist, $sent, true);
        $this->cue($playlist, $unsent, false);
        $this->consume($playlist, $played);

        $this->queueRepo()->clearUnplayed($this->getTestStation());
        $this->em->clear();

        $this->assertQueued($I, $playlist, $sent, true);
        $this->assertQueued($I, $playlist, $unsent, true);
        $this->assertQueued($I, $playlist, $played, false);

        $I->assertCount(0, $this->queueRepo()->getUnplayedQueue($this->getTestStation()));
    }

    /**
     * @before setupComplete
     */
    public function clearUpcomingQueueOnlyRestoresUnsentRows(FunctionalTester $I): void
    {
        $I->wantTo('Leave rows already handed to Liquidsoap alone when clearing the upcoming queue.');

        $playlist = $this->seedSongsPlaylist('Sequential', PlaylistOrders::Sequential, false);
        $sent = $this->addMedia($playlist, 'sent.mp3');
        $unsent = $this->addMedia($playlist, 'unsent.mp3');

        $sentRow = $this->cue($playlist, $sent, true);
        $unsentRow = $this->cue($playlist, $unsent, false);

        $this->queueRepo()->clearUpcomingQueue($this->getTestStation());
        $this->em->clear();

        $this->assertQueued($I, $playlist, $sent, false);
        $this->assertQueued($I, $playlist, $unsent, true);

        $I->assertNotNull($this->em->find(StationQueue::class, $sentRow->id));
        $I->assertNull($this->em->find(StationQueue::class, $unsentRow->id));
    }

    /**
     * @before setupComplete
     */
    public function playedRowsAreLeftAlone(FunctionalTester $I): void
    {
        $I->wantTo('Neither delete nor restore rows that have already been played.');

        $playlist = $this->seedSongsPlaylist('Sequential', PlaylistOrders::Sequential, false);
        $media = $this->addMedia($playlist, 'played.mp3');

        $row = $this->cue($playlist, $media, true);
        $row->is_played = true;
        $this->em->persist($row);
        $this->em->flush();

        $this->queueRepo()->clearUnplayed($this->getTestStation());
        $this->em->clear();

        $this->assertQueued($I, $playlist, $media, false);

        $I->assertNotNull($this->em->find(StationQueue::class, $row->id));
    }

    /**
     * @before setupComplete
     */
    public function rowsWithoutPlaylistAreIgnored(FunctionalTester $I): void
    {
        $I->wantTo('Ignore queue rows that have no playlist, such as requests.');

        $playlist = $this->seedSongsPlaylist('Sequential', PlaylistOrders::Sequential, false);
        $media = $this->addMedia($playlist, 'cued.mp3');
        $requested = $this->addMedia($playlist, 'requested.mp3');

        $this->cue($playlist, $media, false);
        $this->consume($playlist, $requested);

        $station = $this->getTestStation();
        $requestRow = StationQueue::fromMedia($station, $requested);
        $this->em->persist($requestRow);
        $this->em->flush();

        $this->queueRepo()->clearUnplayed($station);
        $this->em->clear();

        $this->assertQueued($I, $playlist, $media, true);
        $this->assertQueued($I, $playlist, $requested, false);

        $I->assertNull($this->em->find(StationQueue::class, $requestRow->id));
    }

    /**
     * @before setupComplete
     */
    public function preservedPlaylistKeepsInFlightTracksAcrossRestart(FunctionalTester $I): void
    {
        $I->wantTo('Keep in-flight tracks of a preserved playlist when the container restarts.');

        $playlist = $this->seedSongsPlaylist('Preserved Sequential', PlaylistOrders::Sequential, true);
        $inFlight = $this->addMedia($playlist, 'in-flight.mp3');
        $played = $this->addMedia($playlist, 'played.mp3');
        $untouched = $this->addMedia($playlist, 'untouched.mp3');

        $this->cue($playlist, $inFlight, true);
        $this->consume($playlist, $played);

        $queueResetAt = $this->em->refetch($playlist)->queue_reset_at;

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-07 12:00:00', 'UTC'));

        try {
            $this->queueRepo()->clearUnplayed();

            $this->em->clear();
            $this->di->get(Configuration::class)->initializeConfiguration($this->getTestStation());
        } finally {
            CarbonImmutable::setTestNow();
        }

        $this->em->clear();

        $this->assertQueued($I, $playlist, $inFlight, true);
        $this->assertQueued($I, $playlist, $played, false);
        $this->assertQueued($I, $playlist, $untouched, true);

        $I->assertSame(
            $queueResetAt?->getTimestamp(),
            $this->em->refetch($playlist)->queue_reset_at?->getTimestamp()
        );
    }

    /**
     * @before setupComplete
     */
    public function invalidatedQueueRowReturnsItsSlot(FunctionalTester $I): void
    {
        $I->wantTo('Hand the slot back when the queue builder drops a row whose playlist is no longer valid.');

        $playlist = $this->seedSongsPlaylist('Sequential', PlaylistOrders::Sequential, false);
        $media = $this->addMedia($playlist, 'cued.mp3');

        $row = $this->cue($playlist, $media, false);
        $rowId = $row->id;

        $playlist->is_enabled = false;
        $this->em->persist($playlist);
        $this->em->flush();

        $this->di->get(Queue::class)->buildQueue($this->getTestStation());
        $this->em->clear();

        $this->assertQueued($I, $playlist, $media, true);

        $I->assertNull($this->em->find(StationQueue::class, $rowId));
    }

    private function queueRepo(): StationQueueRepository
    {
        return $this->di->get(StationQueueRepository::class);
    }

    private function seedSongsPlaylist(
        string $name,
        PlaylistOrders $order,
        bool $preserveQueueOnRestart
    ): StationPlaylist {
        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = $name;
        $playlist->source = PlaylistSources::Songs;
        $playlist->order = $order;
        $playlist->preserve_queue_on_restart = $preserveQueueOnRestart;

        $this->em->persist($playlist);
        $this->em->flush();

        return $playlist;
    }

    private function addMedia(StationPlaylist $playlist, string $path): StationMedia
    {
        $media = $this->uploadTestSong($path);

        $this->di->get(StationPlaylistMediaRepository::class)->addMediaToPlaylist($media, $playlist);
        $this->em->flush();

        return $media;
    }

    private function cue(StationPlaylist $playlist, StationMedia $media, bool $sentToAutoDj): StationQueue
    {
        $this->consume($playlist, $media);

        $row = StationQueue::fromMedia($this->getTestStation(), $media);
        $row->playlist = $playlist;
        $row->sent_to_autodj = $sentToAutoDj;

        $this->em->persist($row);
        $this->em->flush();

        return $row;
    }

    private function consume(StationPlaylist $playlist, StationMedia $media): void
    {
        $stationPlaylistMedia = $this->findPlaylistMedia($playlist, $media);
        $stationPlaylistMedia->played();

        $this->em->persist($stationPlaylistMedia);
        $this->em->flush();
    }

    private function assertQueued(
        FunctionalTester $I,
        StationPlaylist $playlist,
        StationMedia $media,
        bool $expected
    ): void {
        $I->assertSame(
            $expected,
            $this->findPlaylistMedia($playlist, $media)->is_queued,
            sprintf('Media "%s" should%s be queued.', $media->path, $expected ? '' : ' not')
        );
    }

    private function findPlaylistMedia(StationPlaylist $playlist, StationMedia $media): StationPlaylistMedia
    {
        $stationPlaylistMedia = $this->em->getRepository(StationPlaylistMedia::class)->findOneBy([
            'playlist' => $playlist,
            'media' => $media,
        ]);

        if (!($stationPlaylistMedia instanceof StationPlaylistMedia)) {
            throw new RuntimeException(sprintf('No playlist media found for "%s".', $media->path));
        }

        return $stationPlaylistMedia;
    }
}
