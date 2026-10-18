<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\StationMedia;
use App\Sync\Task\CheckMediaTask;
use FunctionalTester;

final class CheckMediaTaskCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function syncFileSizeOfUnchangedMedia(FunctionalTester $I): void
    {
        $I->wantTo('Sync the stored file size of unchanged media without reprocessing it.');

        $mediaId = $this->uploadTestSong()->id;

        // Simulate media that was processed before file sizes were tracked.
        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.size = 0
                WHERE sm.id = :id
            DQL
        )->setParameter('id', $mediaId)
            ->execute();

        $this->di->get(CheckMediaTask::class)->importMusic(
            $this->getTestStation()->media_storage_location
        );

        $this->em->clear();
        $media = $this->em->find(StationMedia::class, $mediaId);

        $I->assertSame((int) filesize(self::TEST_SONG_PATH), $media?->size);
    }
}
