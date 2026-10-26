<?php

declare(strict_types=1);

namespace Functional;

use App\Console\Command\Media\SyncCommand;
use App\Entity\StationMedia;
use FunctionalTester;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class MediaSyncCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function syncCommandAllowsEmptyStorageOnlyWithFlag(FunctionalTester $I): void
    {
        $I->wantTo('Remove the media of an empty media storage only when the sync command allows it.');

        $mediaId = $this->uploadTestSong()->id;
        $station = $this->getTestStation();

        $this->withMediaStorageUnmounted(function () use ($I, $mediaId, $station): void {
            $I->assertSame(Command::FAILURE, $this->runSyncCommand(['station' => (string) $station->id]));
            $this->em->clear();

            $I->assertNotNull($this->em->find(StationMedia::class, $mediaId));

            $I->assertSame(
                Command::SUCCESS,
                $this->runSyncCommand(['station' => $station->short_name, '--allow-empty-storage' => true])
            );
            $this->em->clear();

            $I->assertNull($this->em->find(StationMedia::class, $mediaId));
        });
    }

    public function syncCommandRejectsUnknownStation(FunctionalTester $I): void
    {
        $I->wantTo('Fail the media sync command for an unknown station.');

        $I->assertSame(Command::FAILURE, $this->runSyncCommand(['station' => 'no_such_station']));
    }

    /**
     * @before setupComplete
     */
    public function debugActionSyncsEmptyStorage(FunctionalTester $I): void
    {
        $I->wantTo('Remove the media of an empty media storage from the System Debugger.');

        $mediaId = $this->uploadTestSong()->id;
        $stationId = $this->getTestStation()->id;

        $this->withMediaStorageUnmounted(
            fn() => $I->sendPut("/api/admin/debug/station/{$stationId}/sync-media-allow-empty-storage")
        );

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['context' => ['deleted' => 1]]);

        $this->em->clear();

        $I->assertNull($this->em->find(StationMedia::class, $mediaId));
    }

    /**
     * @before setupComplete
     */
    public function debugStationListLinksTheSyncAction(FunctionalTester $I): void
    {
        $I->wantTo('List the empty storage sync link for each station in the System Debugger.');

        $stationId = $this->getTestStation()->id;
        $I->sendGet('/api/admin/debug/stations');

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'id' => $stationId,
            'syncMediaAllowEmptyStorageUrl' => "/api/admin/debug/station/{$stationId}/sync-media-allow-empty-storage",
        ]);
    }

    /**
     * @param array<string, string|bool> $arguments
     */
    private function runSyncCommand(array $arguments): int
    {
        $commandTester = new CommandTester($this->di->get(SyncCommand::class));

        return $commandTester->execute($arguments);
    }
}
