<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Radio\Backend\Liquidsoap\Command\FeedbackCommand;
use FunctionalTester;
use RuntimeException;

final class LiquidsoapFeedbackCest extends CestAbstract
{
    private const int CUED_AT = 1_788_000_000; // 2026-08-29 10:40:00 UTC

    /**
     * @before setupComplete
     */
    public function fallbackPlayConsumesPlaylistMedia(FunctionalTester $I): void
    {
        $I->wantTo('Mark playlist media as played when Liquidsoap reports a track without a queue row.');

        $playlist = $this->seedPlaylist();
        $played = $this->addMedia($playlist, 'played.mp3');
        $remaining = $this->addMedia($playlist, 'remaining.mp3');

        $this->sendFeedback($I, [
            'media_id' => (string) $played->id,
            'playlist_id' => (string) $playlist->id,
        ]);

        $spm = $this->findPlaylistMedia($playlist, $played);

        $I->assertFalse($spm->is_queued);
        $I->assertGreaterThan(0, $spm->last_played);
        $I->assertTrue($this->findPlaylistMedia($playlist, $remaining)->is_queued);
        $I->assertNull($this->em->refetch($playlist)->queue_reset_at);
    }

    /**
     * @before setupComplete
     */
    public function exhaustedPlaylistIsResetAfterFallbackPlay(FunctionalTester $I): void
    {
        $I->wantTo('Reset the playlist queue once Liquidsoap has played its last queued track.');

        $playlist = $this->seedPlaylist();
        $media = $this->addMedia($playlist, 'only.mp3');

        $this->sendFeedback($I, [
            'media_id' => (string) $media->id,
            'playlist_id' => (string) $playlist->id,
        ]);

        $I->assertTrue($this->findPlaylistMedia($playlist, $media)->is_queued);
        $I->assertNotNull($this->em->refetch($playlist)->queue_reset_at);
    }

    /**
     * @before setupComplete
     */
    public function queuedPlayLeavesPlaylistMediaToTheQueueBuilder(FunctionalTester $I): void
    {
        $I->wantTo('Leave playlist media untouched when Liquidsoap reports a track that has a queue row.');

        $playlist = $this->seedPlaylist();
        $media = $this->addMedia($playlist, 'cued.mp3');

        $spm = $this->findPlaylistMedia($playlist, $media);
        $spm->played(self::CUED_AT);
        $this->em->persist($spm);

        $row = StationQueue::fromMedia($this->getTestStation(), $media);
        $row->playlist = $playlist;
        $row->sent_to_autodj = true;

        $this->em->persist($row);
        $this->em->flush();

        $this->sendFeedback($I, [
            'media_id' => (string) $media->id,
            'playlist_id' => (string) $playlist->id,
            'sq_id' => (string) $row->id,
        ]);

        $spm = $this->findPlaylistMedia($playlist, $media);
        $I->assertFalse($spm->is_queued);
        $I->assertSame(self::CUED_AT, $spm->last_played);

        $row = $this->em->find(StationQueue::class, $row->id);
        $I->assertTrue($row instanceof StationQueue && $row->is_played);
    }

    private function seedPlaylist(): StationPlaylist
    {
        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = 'Sequential';
        $playlist->source = PlaylistSources::Songs;
        $playlist->order = PlaylistOrders::Sequential;

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

    /**
     * @param array<string, string> $payload
     */
    private function sendFeedback(FunctionalTester $I, array $payload): void
    {
        $handled = $this->di->get(FeedbackCommand::class)->run($this->getTestStation(), true, $payload);
        $this->em->clear();

        $I->assertTrue($handled, 'Feedback should be processed.');
    }

    private function findPlaylistMedia(StationPlaylist $playlist, StationMedia $media): StationPlaylistMedia
    {
        $spm = $this->di->get(StationPlaylistMediaRepository::class)->findByPlaylistAndMedia($playlist, $media);

        if ($spm === null) {
            throw new RuntimeException('No playlist media found.');
        }

        return $spm;
    }
}
