<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\Station;
use App\Entity\StationQueue;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

/**
 * Assigns built queue rows their expected play time the way the AutoDJ Queue does.
 * The cue time equals the play time, since the harnesses have no Liquidsoap lookahead.
 */
final class ExpectedPlayTime
{
    /**
     * @param StationQueue[] $queueRows
     */
    public static function assignToBuiltRows(
        Station $station,
        array $queueRows,
        DateTimeImmutable $playTime,
        bool $interrupting
    ): void {
        foreach ($queueRows as $queueRow) {
            if ($interrupting) {
                $queueRow->is_played = true;
            }

            $queueRow->timestamp_cued = $playTime;
            $queueRow->timestamp_played = $playTime;
            $queueRow->updateVisibility();

            $playTime = self::after($station, $playTime, $queueRow->duration);
        }
    }

    /**
     * Mirrors the private Queue::addDurationToTime()
     */
    public static function after(
        Station $station,
        DateTimeImmutable $playTime,
        ?float $duration
    ): CarbonImmutable {
        $duration ??= 1;

        $crossfade = $station->backend_config->getCrossfadeDuration();

        $nextPlayTime = CarbonImmutable::instance($playTime)->addSeconds($duration);

        return ($duration >= $crossfade)
            ? $nextPlayTime->subMilliseconds((int) ($crossfade * 1000))
            : $nextPlayTime;
    }
}
