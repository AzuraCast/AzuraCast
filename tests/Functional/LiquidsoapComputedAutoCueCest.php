<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationMediaComputedAutoCue;
use App\Entity\StationMediaMetadata;
use App\Entity\StationPlaylist;
use App\Entity\StationRequest;
use App\Event\Radio\AnnotateNextSong;
use App\Radio\Backend\Liquidsoap\Command\SaveCacheCommand;
use App\Utilities\Time;
use Doctrine\DBAL\Types\Types;
use FunctionalTester;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

final class LiquidsoapComputedAutoCueCest extends CestAbstract
{
    private const array COMPUTED_VALUES = [
        'cue_in' => 1.5,
        'cue_out' => 20.25,
        'fade_in' => 0.5,
        'fade_out' => 2.75,
        'amplify' => '-2.699 dB',
    ];

    /**
     * @before setupComplete
     */
    public function savedComputedValuesAreAnnotated(FunctionalTester $I): void
    {
        $I->wantTo('Annotate the next song with the AutoCue values Liquidsoap saved for it.');

        $this->enableAutoCue();
        $media = $this->uploadTestSong();

        $annotations = $this->annotate($I, $media);
        $I->assertSame("{$media->id}_{$media->mtime}", $annotations['azuracast_cache_key']);
        $I->assertArrayNotHasKey('azuracast_autocue', $annotations);

        $this->saveComputedValues($I, $annotations['azuracast_cache_key']);

        $annotations = $this->annotate($I, $this->reloadMedia($media));
        $I->assertSame('true', $annotations['azuracast_autocue']);
        $I->assertSame(1.5, (float) $annotations['autocue_cue_in']);
        $I->assertSame(20.25, (float) $annotations['autocue_cue_out']);
        $I->assertSame(0.5, (float) $annotations['autocue_fade_in']);
        $I->assertSame(2.75, (float) $annotations['autocue_fade_out']);
        $I->assertSame('-2.699 dB', $annotations['liq_amplify']);
    }

    /**
     * @before setupComplete
     */
    public function computedValuesAreStoredOnTheMedia(FunctionalTester $I): void
    {
        $I->wantTo('Store the AutoCue values Liquidsoap computed on the media.');

        $media = $this->uploadTestSong();

        $timeBeforeSave = Time::nowUtc()->getTimestamp();
        $this->saveComputedValues($I, $this->cacheKeyFor($media));
        $timeAfterSave = Time::nowUtc()->getTimestamp();

        $computedAutoCue = $this->requireComputedAutoCue($media);
        $I->assertSame($media->mtime, $computedAutoCue->mtime);
        $I->assertGreaterThanOrEqual($timeBeforeSave, $computedAutoCue->computed_at);
        $I->assertLessThanOrEqual($timeAfterSave, $computedAutoCue->computed_at);
        $I->assertSame(
            [
                'amplify' => -2.699,
                'cue_in' => 1.5,
                'cue_out' => 20.25,
                'fade_in' => 0.5,
                'fade_out' => 2.75,
            ],
            $computedAutoCue->values
        );
    }

    /**
     * @before setupComplete
     */
    public function computedStartNextIsAnnotated(FunctionalTester $I): void
    {
        $I->wantTo('Keep the crossfade start point Liquidsoap computed for a track.');

        $this->enableAutoCue();
        $media = $this->uploadTestSong();

        $this->saveComputedValues(
            $I,
            $this->cacheKeyFor($media),
            [...self::COMPUTED_VALUES, 'start_next' => 18.5]
        );

        $annotations = $this->annotate($I, $this->reloadMedia($media));
        $I->assertSame(18.5, (float) $annotations['autocue_start_next']);
    }

    /**
     * @before setupComplete
     */
    public function stationWithoutAutoCueIgnoresComputedValues(FunctionalTester $I): void
    {
        $I->wantTo('Leave out saved AutoCue values on a station with AutoCue disabled.');

        $I->assertFalse($this->getTestStation()->backend_config->enable_auto_cue);
        $media = $this->uploadTestSong();

        $this->saveComputedValues($I, $this->cacheKeyFor($media));

        $annotations = $this->annotate($I, $this->reloadMedia($media));
        $I->assertSame((string) $media->id, $annotations['media_id']);
        $I->assertArrayNotHasKey('azuracast_cache_key', $annotations);
        $I->assertArrayNotHasKey('azuracast_autocue', $annotations);
        $I->assertArrayNotHasKey('liq_amplify', $annotations);
    }

    /**
     * @before setupComplete
     */
    public function manualAmplifyWinsOverComputedAmplify(FunctionalTester $I): void
    {
        $I->wantTo('Keep a manually set amplify when Liquidsoap saved a computed one.');

        $this->enableAutoCue();
        $media = $this->uploadTestSong();
        $media->extra_metadata = [StationMediaMetadata::AMPLIFY => -3.0];
        $this->em->persist($media);
        $this->em->flush();

        $this->saveComputedValues($I, $this->cacheKeyFor($media));

        $annotations = $this->annotate($I, $this->reloadMedia($media));
        $I->assertSame('-3 dB', $annotations['liq_amplify']);
        $I->assertSame('true', $annotations['azuracast_autocue']);
        $I->assertSame(1.5, (float) $annotations['autocue_cue_in']);
        $I->assertSame(20.25, (float) $annotations['autocue_cue_out']);
    }

    /**
     * @before setupComplete
     */
    public function outdatedMtimeSavesNothing(FunctionalTester $I): void
    {
        $I->wantTo('Discard AutoCue values Liquidsoap computed for an older version of the file.');

        $media = $this->uploadTestSong();
        $outdatedMtime = $media->mtime - 1;

        $this->saveComputedValues($I, "{$media->id}_{$outdatedMtime}");

        $I->assertNull($this->reloadMedia($media)->computed_auto_cue);
    }

    /**
     * @before setupComplete
     */
    public function changedMtimeIsAMiss(FunctionalTester $I): void
    {
        $I->wantTo('Ignore stored AutoCue values once the file has changed.');

        $this->enableAutoCue();
        $media = $this->uploadTestSong();
        $this->saveComputedValues($I, $this->cacheKeyFor($media));

        $media = $this->reloadMedia($media);
        $media->mtime += 10;
        $this->em->persist($media);
        $this->em->flush();

        $annotations = $this->annotate($I, $media);
        $I->assertSame($this->cacheKeyFor($media), $annotations['azuracast_cache_key']);
        $I->assertArrayNotHasKey('azuracast_autocue', $annotations);
        $I->assertNotNull($this->reloadMedia($media)->computed_auto_cue);
    }

    /**
     * @before setupComplete
     */
    public function expiredComputedValuesAreAMiss(FunctionalTester $I): void
    {
        $I->wantTo('Ignore stored AutoCue values older than seven days.');

        $this->enableAutoCue();
        $media = $this->uploadTestSong();
        $this->saveComputedValues($I, $this->cacheKeyFor($media));
        $this->setComputedAt($media, Time::nowUtc()->subDays(7)->subMinute()->getTimestamp());

        $annotations = $this->annotate($I, $this->reloadMedia($media));
        $I->assertArrayHasKey('azuracast_cache_key', $annotations);
        $I->assertArrayNotHasKey('azuracast_autocue', $annotations);
    }

    /**
     * @before setupComplete
     */
    public function computedValuesUnderMaxAgeAreAHit(FunctionalTester $I): void
    {
        $I->wantTo('Use stored AutoCue values that are just under seven days old.');

        $this->enableAutoCue();
        $media = $this->uploadTestSong();
        $this->saveComputedValues($I, $this->cacheKeyFor($media));
        $this->setComputedAt($media, Time::nowUtc()->subDays(7)->addMinute()->getTimestamp());

        $annotations = $this->annotate($I, $this->reloadMedia($media));
        $I->assertSame('true', $annotations['azuracast_autocue']);
    }

    /**
     * @before setupComplete
     */
    public function otherStorageLocationSavesNothing(FunctionalTester $I): void
    {
        $I->wantTo('Reject AutoCue values from a station that does not own the media.');

        $media = $this->uploadTestSong();
        $otherStation = $this->createOtherStation($I);
        $I->assertNotSame(
            $this->getTestStation()->media_storage_location->id,
            $otherStation->media_storage_location->id
        );

        $I->assertTrue($this->saveCache($otherStation, $this->cacheKeyFor($media)));
        $I->assertNull($this->reloadMedia($media)->computed_auto_cue);

        $I->sendDelete("/api/admin/station/{$otherStation->id}");
    }

    /**
     * @before setupComplete
     */
    public function malformedCacheKeyIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('Reject AutoCue values sent with a malformed or outdated cache key.');

        $media = $this->uploadTestSong();

        $I->assertFalse($this->saveCache($this->getTestStation(), 'not-a-key'));
        $I->assertFalse($this->saveCache($this->getTestStation(), 'c6084955ca1a462b49cb1a1e_1789317273'));
        $I->assertNull($this->reloadMedia($media)->computed_auto_cue);
    }

    /**
     * @before setupComplete
     */
    public function playlistOrderOmitsComputedValues(FunctionalTester $I): void
    {
        $I->wantTo('Leave stored AutoCue values out of the playlist order API.');

        $media = $this->uploadTestSong();
        $playlist = $this->seedSequentialPlaylist($media);
        $this->saveComputedValues($I, $this->cacheKeyFor($media));

        $station = $this->getTestStation();
        $I->sendGet("/api/station/{$station->id}/playlist/{$playlist->id}/order");

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContains('extra_metadata_raw');
        $I->dontSeeResponseContains('computed_auto_cue');
    }

    /**
     * @before setupComplete
     */
    public function requestReportOmitsComputedValues(FunctionalTester $I): void
    {
        $I->wantTo('Leave stored AutoCue values out of the requests report API.');

        $media = $this->uploadTestSong();
        $this->saveComputedValues($I, $this->cacheKeyFor($media));

        $station = $this->getTestStation();
        $this->em->persist(new StationRequest($station, $media, '127.0.0.1'));
        $this->em->flush();

        $I->sendGet("/api/station/{$station->id}/reports/requests");

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContains('extra_metadata_raw');
        $I->dontSeeResponseContains('computed_auto_cue');
    }

    /**
     * @before setupComplete
     */
    public function mediaEditCannotWriteComputedValues(FunctionalTester $I): void
    {
        $I->wantTo('Keep stored AutoCue values unchanged when the media is edited via API.');

        $media = $this->uploadTestSong();
        $this->saveComputedValues($I, $this->cacheKeyFor($media));
        $this->em->clear();

        $forgedComputedAutoCue = [
            'mtime' => 1,
            'computed_at' => 1,
            'values' => ['cue_in' => 9.0],
        ];

        $station = $this->getTestStation();
        $I->sendPut("/api/station/{$station->id}/file/{$media->id}", [
            'title' => 'Edited Title',
            'computed_auto_cue' => $forgedComputedAutoCue,
            'computed_auto_cue_raw' => $forgedComputedAutoCue,
        ]);
        $I->seeResponseCodeIsSuccessful();

        $I->assertSame('Edited Title', $this->reloadMedia($media)->title);

        $computedAutoCue = $this->requireComputedAutoCue($media);
        $I->assertSame($media->mtime, $computedAutoCue->mtime);
        $I->assertSame(1.5, $computedAutoCue->values['cue_in']);
    }

    private function enableAutoCue(): void
    {
        $station = $this->getTestStation();

        $backendConfig = $station->backend_config;
        $backendConfig->enable_auto_cue = true;
        $station->backend_config = $backendConfig;

        $this->em->persist($station);
        $this->em->flush();
    }

    /**
     * @return array<string, string>
     */
    private function annotate(FunctionalTester $I, StationMedia $media): array
    {
        $event = AnnotateNextSong::fromStationMedia($this->getTestStation(), $media);
        $this->di->get(EventDispatcherInterface::class)->dispatch($event);

        return $this->parseAnnotations($I, $event->buildAnnotations());
    }

    /**
     * @return array<string, string>
     */
    private function parseAnnotations(FunctionalTester $I, string $annotated): array
    {
        $I->assertStringStartsWith('annotate:', $annotated);

        preg_match_all('/(\w+)="((?:[^"\\\\]|\\\\.)*)"/', $annotated, $matches);

        return array_combine($matches[1], $matches[2]);
    }

    /**
     * @param array<string, mixed> $computedValues
     */
    private function saveComputedValues(
        FunctionalTester $I,
        string $cacheKey,
        array $computedValues = self::COMPUTED_VALUES
    ): void {
        $I->assertTrue(
            $this->saveCache($this->getTestStation(), $cacheKey, $computedValues),
            'Computed values should be saved.'
        );
    }

    /**
     * @param array<string, mixed> $computedValues
     */
    private function saveCache(
        Station $station,
        string $cacheKey,
        array $computedValues = self::COMPUTED_VALUES
    ): mixed {
        return $this->di->get(SaveCacheCommand::class)->run(
            $station,
            true,
            [
                'cache_key' => $cacheKey,
                'data' => $computedValues,
            ]
        );
    }

    private function setComputedAt(StationMedia $media, int $computedAt): void
    {
        $computedAutoCue = $this->requireComputedAutoCue($media);
        $computedAutoCue->computed_at = $computedAt;

        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.computed_auto_cue_raw = :computedAutoCue
                WHERE sm.id = :id
            DQL
        )->setParameter('computedAutoCue', $computedAutoCue->toArray(), Types::JSON)
            ->setParameter('id', $media->id)
            ->execute();
    }

    private function requireComputedAutoCue(StationMedia $media): StationMediaComputedAutoCue
    {
        $computedAutoCue = $this->reloadMedia($media)->computed_auto_cue;

        if ($computedAutoCue === null) {
            throw new RuntimeException('No computed AutoCue values stored.');
        }

        return $computedAutoCue;
    }

    private function reloadMedia(StationMedia $media): StationMedia
    {
        $this->em->clear();

        return $this->em->refetch($media);
    }

    private function cacheKeyFor(StationMedia $media): string
    {
        return "{$media->id}_{$media->mtime}";
    }

    private function createOtherStation(FunctionalTester $I): Station
    {
        $I->sendPost('/api/admin/stations', [
            'name' => 'Other Functional Test Radio',
            'description' => 'Station without access to the test media.',
        ]);

        $stationId = $I->grabDataFromResponseByJsonPath('$.id');
        $station = $this->em->find(Station::class, $stationId[0]);

        if ($station === null) {
            throw new RuntimeException('Other station was not created.');
        }

        return $station;
    }

    private function seedSequentialPlaylist(StationMedia $media): StationPlaylist
    {
        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = 'Sequential';
        $playlist->source = PlaylistSources::Songs;
        $playlist->order = PlaylistOrders::Sequential;

        $this->em->persist($playlist);
        $this->em->flush();

        $this->di->get(StationPlaylistMediaRepository::class)->addMediaToPlaylist($media, $playlist);
        $this->em->flush();

        return $playlist;
    }
}
