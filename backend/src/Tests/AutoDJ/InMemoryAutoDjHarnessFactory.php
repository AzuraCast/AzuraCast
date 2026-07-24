<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Repository\StationRequestRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationRequest;
use App\Radio\AutoDJ\DuplicatePrevention;
use App\Radio\AutoDJ\QueueBuilder;
use App\Radio\AutoDJ\Scheduler;
use App\Tests\AutoDJ\Scenario\ScenarioRuntime;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Mockery;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\SimpleCache\CacheInterface;

/**
 * Needs DG\BypassFinals configured & enabled to mock repositories due to them being marked as final
 */
final class InMemoryAutoDjHarnessFactory
{
    /**
     * @param array<string, mixed> $dump
     */
    public function create(array $dump, ScenarioRuntime $runtime): InMemoryAutoDjHarness
    {
        $entities = (new InMemoryEntityHydrator())->hydrate($dump, $runtime);

        $logHandler = new TestHandler();

        $logger = new Logger('test_autodj');
        $logger->pushHandler($logHandler);

        $duplicatePrevention = new DuplicatePrevention();
        $duplicatePrevention->setLogger($logger);

        $dataProxy = new InMemoryAutoDjDataProxy($entities, $duplicatePrevention);

        $spmRepo = $this->fakeSpmRepo($dataProxy);
        $spRepo = $this->fakeSpRepo($dataProxy);
        $queueRepo = $this->fakeQueueRepo($dataProxy);
        $requestRepo = $this->fakeRequestRepo($dataProxy);

        $entityManager = $this->fakeEntityManager($dataProxy);

        $scheduler = new Scheduler($spRepo, $spmRepo, $queueRepo);
        $scheduler->setEntityManager($entityManager);
        $scheduler->setLogger($logger);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->allows('get')->andReturnNull();
        $cache->allows('set')->andReturnTrue();

        $queueBuilder = new QueueBuilder(
            $scheduler,
            $duplicatePrevention,
            $cache,
            $spRepo,
            $spmRepo,
            $requestRepo,
            $queueRepo
        );
        $queueBuilder->setEntityManager($entityManager);
        $queueBuilder->setLogger($logger);

        return new InMemoryAutoDjHarness(
            $entities,
            $scheduler,
            $queueBuilder,
            $dataProxy,
            $logHandler
        );
    }

    private function fakeEntityManager(InMemoryAutoDjDataProxy $dataProxy): ReloadableEntityManagerInterface
    {
        $entityManager = Mockery::mock(ReloadableEntityManagerInterface::class);
        $entityManager->allows('persist');
        $entityManager->allows('flush');
        $entityManager->allows('remove');
        $entityManager->allows('refetch')->andReturnUsing(static fn(object $entity): object => $entity);
        $entityManager->allows('find')->andReturnUsing(
            static fn(string $className, int|string $id): ?object => $dataProxy->find($className, $id)
        );

        return $entityManager;
    }

    private function fakeSpmRepo(InMemoryAutoDjDataProxy $dataProxy): StationPlaylistMediaRepository
    {
        $repo = Mockery::mock(StationPlaylistMediaRepository::class);

        $repo->allows('getQueue')->andReturnUsing(
            static fn(StationPlaylist $playlist): array => $dataProxy->getQueue($playlist)
        );

        $repo->allows('resetQueue')->andReturnUsing(
            static function (StationPlaylist $playlist, ?CarbonImmutable $now = null) use ($dataProxy): void {
                $dataProxy->resetQueue($playlist, $now);
            }
        );

        $repo->allows('isQueueEmpty')->andReturnUsing(
            static fn(StationPlaylist $playlist): bool => $dataProxy->isQueueEmpty($playlist)
        );

        $repo->allows('isQueueCompletelyFilled')->andReturnUsing(
            static fn(StationPlaylist $playlist): bool => $dataProxy->isQueueCompletelyFilled($playlist)
        );

        $repo->allows('isMediaInPlaylist')->andReturnUsing(
            static fn(
                StationMedia $media,
                StationPlaylist $playlist
            ): bool => $dataProxy->isMediaInPlaylist($media, $playlist)
        );

        return $repo;
    }

    private function fakeSpRepo(InMemoryAutoDjDataProxy $dataProxy): StationPlaylistRepository
    {
        $repo = Mockery::mock(StationPlaylistRepository::class);

        $repo->allows('getPlaylistGroupQueue')->andReturnUsing(
            static fn(StationPlaylist $playlist): array => $dataProxy->getPlaylistGroupQueue($playlist)
        );

        $repo->allows('resetPlaylistGroupQueue')->andReturnUsing(
            static function (StationPlaylist $playlist, ?CarbonImmutable $now = null) use ($dataProxy): void {
                $dataProxy->resetPlaylistGroupQueue($playlist, $now);
            }
        );

        $repo->allows('isPlaylistGroupQueueEmpty')->andReturnUsing(
            static fn(StationPlaylist $playlist): bool => $dataProxy->isPlaylistGroupQueueEmpty($playlist)
        );

        $repo->allows('isPlaylistGroupQueueCompletelyFilled')->andReturnUsing(
            static fn(StationPlaylist $playlist): bool => $dataProxy->isPlaylistGroupQueueCompletelyFilled($playlist)
        );

        return $repo;
    }

    private function fakeQueueRepo(InMemoryAutoDjDataProxy $dataProxy): StationQueueRepository
    {
        $repo = Mockery::mock(StationQueueRepository::class);

        $repo->allows('getRecentlyPlayedByTimeRange')->andReturnUsing(
            static fn(
                Station $station,
                DateTimeImmutable $now,
                int $minutes
            ): array => $dataProxy->getRecentlyPlayedByTimeRange($now, $minutes)
        );

        $repo->allows('isPlaylistRecentlyPlayed')->andReturnUsing(
            static fn(
                StationPlaylist $playlist,
                ?int $playPerSongs = null
            ): bool => $dataProxy->isPlaylistRecentlyPlayed($playlist, $playPerSongs)
        );

        $repo->allows('hasCuedPlaylistMedia')->andReturnUsing(
            static fn(StationPlaylist $playlist): bool => $dataProxy->hasCuedPlaylistMedia($playlist)
        );

        $repo->allows('hasCuedPlaylistGroupMedia')->andReturnUsing(
            static fn(StationPlaylist $playlist): bool => $dataProxy->hasCuedPlaylistGroupMedia($playlist)
        );

        return $repo;
    }

    private function fakeRequestRepo(InMemoryAutoDjDataProxy $dataProxy): StationRequestRepository
    {
        $repo = Mockery::mock(StationRequestRepository::class);

        $repo->allows('getNextPlayableRequest')->andReturnUsing(
            static fn(
                Station $station,
                ?DateTimeImmutable $now = null
            ): ?StationRequest => $dataProxy->getNextPlayableRequest($station, $now)
        );

        return $repo;
    }
}
