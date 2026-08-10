<?php

declare(strict_types=1);

namespace Unit;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Song;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StorageLocation;
use App\Tests\Module;
use Codeception\Test\Unit;
use UnitTester;

class StationPlaylistMediaRepositoryPersistenceTest extends Unit
{
    protected UnitTester $tester;

    private ReloadableEntityManagerInterface $em;

    private StationPlaylistMediaRepository $repo;

    protected function _inject(Module $testsModule): void
    {
        $this->em = $testsModule->em;
        $this->repo = $testsModule->container->get(StationPlaylistMediaRepository::class);
    }

    public function testMarkMediaPlayedAdvancesSequentialPlaylistCursor(): void
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $this->runMarkMediaPlayedAdvancesSequentialPlaylistCursorTest();
        } finally {
            $connection->rollBack();
            $this->em->clear();
        }
    }

    private function runMarkMediaPlayedAdvancesSequentialPlaylistCursorTest(): void
    {
        $station = new Station();
        $station->name = 'Station Playlist Media Repository Test';
        $station->radio_base_dir = $station->short_name;
        $station->media_storage_location = new StorageLocation(
            StorageLocationTypes::StationMedia,
            StorageLocationAdapters::Local
        );
        $station->recordings_storage_location = new StorageLocation(
            StorageLocationTypes::StationRecordings,
            StorageLocationAdapters::Local
        );
        $station->podcasts_storage_location = new StorageLocation(
            StorageLocationTypes::StationPodcasts,
            StorageLocationAdapters::Local
        );

        $playlist = new StationPlaylist($station);
        $playlist->name = 'Sequential Playlist';
        $playlist->order = PlaylistOrders::Sequential;

        $media = new StationMedia($station->media_storage_location, 'test.mp3');
        $media->setSong(Song::createFromText('Test Song'));

        $playlistMedia = new StationPlaylistMedia($playlist, $media);

        $this->em->persist($station->media_storage_location);
        $this->em->persist($station->recordings_storage_location);
        $this->em->persist($station->podcasts_storage_location);
        $this->em->persist($station);
        $this->em->persist($playlist);
        $this->em->persist($media);
        $this->em->persist($playlistMedia);
        $this->em->flush();

        $playlistMediaId = $playlistMedia->id;

        $this->repo->markMediaPlayed($playlist, $media, 123);
        $this->em->clear();

        $playlistMedia = $this->repo->requireRecord($playlistMediaId);

        self::assertFalse($playlistMedia->is_queued);
        self::assertSame(123, $playlistMedia->last_played);
    }
}
