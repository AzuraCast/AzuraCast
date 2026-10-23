<?php

declare(strict_types=1);

namespace Functional;

use App\Cache\AutoCueCache;
use App\Entity\StationMedia;
use App\Entity\StationMediaMetadata;
use App\Event\Radio\AnnotateNextSong;
use App\Radio\Backend\Liquidsoap\Command\SaveCacheCommand;
use FunctionalTester;
use Psr\EventDispatcher\EventDispatcherInterface;

final class LiquidsoapAutoCueCacheCest extends CestAbstract
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
        $I->assertSame("{$media->unique_id}_{$media->mtime}", $annotations['azuracast_cache_key']);
        $I->assertArrayNotHasKey('azuracast_autocue', $annotations);

        $this->saveComputedValues($I, $annotations['azuracast_cache_key']);

        $annotations = $this->annotate($I, $media);
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
    public function stationWithoutAutoCueIgnoresComputedValues(FunctionalTester $I): void
    {
        $I->wantTo('Leave out saved AutoCue values on a station with AutoCue disabled.');

        $I->assertFalse($this->getTestStation()->backend_config->enable_auto_cue);
        $media = $this->uploadTestSong();

        $this->saveComputedValues($I, $this->di->get(AutoCueCache::class)->getCacheKey($media));

        $annotations = $this->annotate($I, $media);
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

        $this->saveComputedValues($I, $this->di->get(AutoCueCache::class)->getCacheKey($media));

        $annotations = $this->annotate($I, $media);
        $I->assertSame('-3 dB', $annotations['liq_amplify']);
        $I->assertSame('true', $annotations['azuracast_autocue']);
        $I->assertSame(1.5, (float) $annotations['autocue_cue_in']);
        $I->assertSame(20.25, (float) $annotations['autocue_cue_out']);
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

    private function saveComputedValues(FunctionalTester $I, string $cacheKey): void
    {
        $saved = $this->di->get(SaveCacheCommand::class)->run(
            $this->getTestStation(),
            true,
            [
                'cache_key' => $cacheKey,
                'data' => self::COMPUTED_VALUES,
            ]
        );

        $I->assertTrue($saved, 'Computed values should be saved.');
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
}
