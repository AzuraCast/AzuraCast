<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PodcastSources;
use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\StationPlaylist;
use App\Sync\Task\CheckPodcastPlaylistsTask;
use FunctionalTester;
use RuntimeException;
use SimpleXMLElement;

final class Frontend_PodcastFeedCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function seeUploadedEpisodeInFeed(FunctionalTester $I): void
    {
        $I->wantTo('Verify the RSS feed enclosure of a podcast episode with uploaded media.');

        $podcast = $this->createPodcast(PodcastSources::Manual);

        $episode = new PodcastEpisode($podcast);
        $episode->title = 'Uploaded Episode';
        $episode->description = 'A podcast episode with uploaded media.';

        $this->em->persist($episode);
        $this->em->flush();

        // The uploaded file is moved into the podcast storage, so upload a copy of the test song.
        $uploadPath = sys_get_temp_dir() . '/podcast_feed_test.mp3';
        copy(self::TEST_SONG_PATH, $uploadPath);

        $this->di->get(PodcastEpisodeRepository::class)->uploadMedia($episode, 'episode.mp3', $uploadPath);

        $podcastMedia = $episode->media;
        if ($podcastMedia === null) {
            throw new RuntimeException('Failed to upload the podcast episode media.');
        }

        $expectedSize = (int) filesize(self::TEST_SONG_PATH);
        $I->assertSame($expectedSize, $podcastMedia->size);

        $station = $this->getTestStation();

        $I->sendGet('/api/station/' . $station->id . '/podcast/' . $podcast->id . '/episode/' . $episode->id);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'media' => [
                'size' => $expectedSize,
            ],
        ]);

        $this->assertFeedEnclosure($I, $podcast, $expectedSize, $podcastMedia->length);
    }

    /**
     * @before setupComplete
     */
    public function seePlaylistEpisodeInFeed(FunctionalTester $I): void
    {
        $I->wantTo('Verify the RSS feed enclosure of a playlist-based podcast episode.');

        $media = $this->uploadTestSong();

        $expectedSize = (int) filesize(self::TEST_SONG_PATH);
        $I->assertSame($expectedSize, $media->size);

        // Processing sets the modified time slightly ahead, which would schedule the episode in the future.
        $media->mtime = time() - 60;
        $this->em->persist($media);

        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = 'Podcast Playlist';

        $this->em->persist($playlist);
        $this->em->flush();

        $this->di->get(StationPlaylistMediaRepository::class)->addMediaToPlaylist($media, $playlist);
        $this->em->flush();

        $podcast = $this->createPodcast(PodcastSources::Playlist, $playlist);

        $this->di->get(CheckPodcastPlaylistsTask::class)->syncPodcastPlaylists($this->getTestStation());

        $this->assertFeedEnclosure($I, $podcast, $expectedSize, $media->length);
    }

    private function createPodcast(PodcastSources $source, ?StationPlaylist $playlist = null): Podcast
    {
        $station = $this->getTestStation();

        $podcast = new Podcast($station->podcasts_storage_location);
        $podcast->source = $source;
        $podcast->playlist = $playlist;
        $podcast->title = 'Feed Test Podcast';
        $podcast->description = 'A functional test podcast.';
        $podcast->language = 'en';
        $podcast->author = 'AzuraCast';
        $podcast->email = 'demo@azuracast.com';

        $this->em->persist($podcast);
        $this->em->flush();

        return $podcast;
    }

    private function assertFeedEnclosure(
        FunctionalTester $I,
        Podcast $podcast,
        int $expectedSize,
        float $expectedDuration
    ): void {
        $station = $this->getTestStation();

        $I->sendGet('/public/' . $station->id . '/podcast/' . $podcast->id . '/feed');
        $I->seeResponseCodeIs(200);

        $feed = new SimpleXMLElement($I->grabResponse());
        $item = $feed->channel->item[0];

        $I->assertSame((string) $expectedSize, (string) $item->enclosure['length']);
        $I->assertSame('audio/mpeg', (string) $item->enclosure['type']);
        $I->assertSame(
            (string) round($expectedDuration),
            (string) $item->children('itunes', true)->duration
        );
    }
}
