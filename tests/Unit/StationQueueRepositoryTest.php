<?php

declare(strict_types=1);

namespace Unit;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Song;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Entity\StorageLocation;
use App\Tests\Module;
use Codeception\Test\Unit;
use UnitTester;

class StationQueueRepositoryTest extends Unit
{
    protected UnitTester $tester;

    private ReloadableEntityManagerInterface $em;

    private StationQueueRepository $repo;

    private StationPlaylistMediaRepository $spmRepo;

    protected function _inject(Module $testsModule): void
    {
        $this->em = $testsModule->em;
        $this->repo = $testsModule->container->get(StationQueueRepository::class);
        $this->spmRepo = $testsModule->container->get(StationPlaylistMediaRepository::class);
    }

    public function testClearUnplayedRestoresSequentialPlaylistMedia(): void
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $this->runClearUnplayedRestoresSequentialPlaylistMediaTest();
        } finally {
            $connection->rollBack();
            $this->em->clear();
        }
    }

    private function runClearUnplayedRestoresSequentialPlaylistMediaTest(): void
    {
        $station = new Station();
        $station->name = 'Station Queue Repository Test';
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
        $media->setSong(Song::createFromText('Queued Song'));

        $playlistMedia = new StationPlaylistMedia($playlist, $media);
        $playlistMedia->is_queued = false;

        $queueRow = StationQueue::fromMedia($station, $media);
        $queueRow->playlist = $playlist;
        $queueRow->sent_to_autodj = true;

        $this->em->persist($station->media_storage_location);
        $this->em->persist($station->recordings_storage_location);
        $this->em->persist($station->podcasts_storage_location);
        $this->em->persist($station);
        $this->em->persist($playlist);
        $this->em->persist($media);
        $this->em->persist($playlistMedia);
        $this->em->persist($queueRow);
        $this->em->flush();

        $playlistMediaId = $playlistMedia->id;
        $queueRowId = $queueRow->id;

        $this->repo->clearUnplayed($station);
        $this->em->clear();

        $playlistMedia = $this->spmRepo->requireRecord($playlistMediaId);

        self::assertTrue($playlistMedia->is_queued);
        self::assertNull($this->repo->find($queueRowId));
    }
}
