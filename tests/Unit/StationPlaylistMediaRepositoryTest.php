<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StorageLocation;
use App\Tests\Module;
use Codeception\Test\Unit;
use UnitTester;

class StationPlaylistMediaRepositoryTest extends Unit
{
    protected UnitTester $tester;

    protected StationPlaylistMediaRepository $repo;

    protected function _inject(Module $testsModule): void
    {
        $this->repo = $testsModule->container->get(StationPlaylistMediaRepository::class);
    }

    public function testSequentialPlaylistQueuesAreNotResetByDefault(): void
    {
        $station = new Station();
        $playlist = $this->createPlaylist($station, PlaylistOrders::Sequential);

        $playlistMedia = $this->createPlaylistMedia($playlist);
        $playlistMedia->is_queued = false;

        $this->repo->resetAllQueues($station);

        self::assertFalse($playlistMedia->is_queued);
    }

    public function testPlaylistQueueResetPolicy(): void
    {
        $station = new Station();
        $sequentialPlaylist = $this->createPlaylist($station, PlaylistOrders::Sequential);
        $shuffledPlaylist = $this->createPlaylist($station, PlaylistOrders::Shuffle);
        $randomPlaylist = $this->createPlaylist($station, PlaylistOrders::Random);

        self::assertFalse($this->repo->shouldResetPlaylistQueue($sequentialPlaylist));
        self::assertTrue($this->repo->shouldResetPlaylistQueue($sequentialPlaylist, includeSequential: true));
        self::assertTrue($this->repo->shouldResetPlaylistQueue($shuffledPlaylist));
        self::assertTrue($this->repo->shouldResetPlaylistQueue($randomPlaylist));
    }

    private function createPlaylist(
        Station $station,
        PlaylistOrders $order
    ): StationPlaylist {
        $playlist = new StationPlaylist($station);
        $playlist->name = $order->value . ' Playlist';
        $playlist->order = $order;

        $station->playlists->add($playlist);

        return $playlist;
    }

    private function createPlaylistMedia(StationPlaylist $playlist): StationPlaylistMedia
    {
        $storageLocation = new StorageLocation(
            StorageLocationTypes::StationMedia,
            StorageLocationAdapters::Local
        );

        $media = new StationMedia($storageLocation, 'test.mp3');
        $playlistMedia = new StationPlaylistMedia($playlist, $media);

        $playlist->media_items->add($playlistMedia);
        $media->playlists->add($playlistMedia);

        return $playlistMedia;
    }
}
