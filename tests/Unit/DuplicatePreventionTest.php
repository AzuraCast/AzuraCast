<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Api\StationPlaylistQueue;
use App\Radio\AutoDJ\DuplicatePrevention;
use App\Tests\Module;
use Codeception\Test\Unit;
use UnitTester;

class DuplicatePreventionTest extends Unit
{
    protected UnitTester $tester;

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
                'title' => 'Best of You',
                'artist' => 'Foo Fighters',
            ],
        ];
        $fullDuplicateResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $fullDuplicateTest);
        $this->assertNull($fullDuplicateResult);

        $artistDuplicateTest = [
            [
                'title' => 'Everlong',
                'artist' => 'Foo Fighters',
            ],
        ];
        $artistDuplicateResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $artistDuplicateTest);
        $this->assertNull($artistDuplicateResult);

        $partialDuplicateTest = [
            [
                'title' => 'Testing Song',
                'artist' => 'Foo Fighters feat. Fall Out Boy',
            ],
        ];
        $partialDuplicateResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $partialDuplicateTest);
        $this->assertNull($partialDuplicateResult);

        $noDuplicatesTest = [
            [
                'title' => 'Testing Song 1',
                'artist' => 'Panic! at the Disco',
            ],
            [
                'title' => 'Lost Memory',
                'artist' => '削除',
            ],
        ];
        $noDuplicatesResult = $this->duplicatePrevention->getDistinctTrack($eligibleTracks, $noDuplicatesTest);
        $this->assertNotNull($noDuplicatesResult);
    }
}
