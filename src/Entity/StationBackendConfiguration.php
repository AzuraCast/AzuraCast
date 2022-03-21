<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\StationBackendPerformanceModes;
use App\Radio\Enums\StreamFormats;
use Doctrine\Common\Collections\ArrayCollection;

class StationBackendConfiguration extends ArrayCollection
{
    public const CHARSET = 'charset';

    public function getCharset(): string
    {
        return $this->get(self::CHARSET) ?? 'UTF-8';
    }

    public function setCharset(?string $charset): void
    {
        $this->set(self::CHARSET, $charset);
    }

    public const DJ_PORT = 'dj_port';

    public function getDjPort(): ?int
    {
        $port = $this->get(self::DJ_PORT);
        return is_numeric($port) ? (int)$port : null;
    }

    public function setDjPort(?int $port): void
    {
        $this->set(self::DJ_PORT, $port);
    }

    public const TELNET_PORT = 'telnet_port';

    public function getTelnetPort(): ?int
    {
        $port = $this->get(self::TELNET_PORT);
        return is_numeric($port) ? (int)$port : null;
    }

    public function setTelnetPort(?int $port): void
    {
        $this->set(self::TELNET_PORT, $port);
    }

    public const RECORD_STREAMS = 'record_streams';

    public function recordStreams(): bool
    {
        return (bool)($this->get(self::RECORD_STREAMS) ?? false);
    }

    public function setRecordStreams(?bool $recordStreams): void
    {
        $this->set(self::RECORD_STREAMS, $recordStreams);
    }

    public const RECORD_STREAMS_FORMAT = 'record_streams_format';

    public function getRecordStreamsFormat(): ?string
    {
        return $this->get(self::RECORD_STREAMS_FORMAT);
    }

    public function getRecordStreamsFormatEnum(): ?StreamFormats
    {
        $recordStreamsFormat = $this->getRecordStreamsFormat();
        return StreamFormats::tryFrom(strtolower($recordStreamsFormat ?? ''));
    }

    public function setRecordStreamsFormat(?string $format): void
    {
        if (null !== $format) {
            $format = strtolower($format);
        }

        if (null !== $format && null === StreamFormats::tryFrom($format)) {
            throw new \InvalidArgumentException('Invalid recording type specified.');
        }

        $this->set(self::RECORD_STREAMS_FORMAT, $format);
    }

    public const USE_MANUAL_AUTODJ = 'use_manual_autodj';

    public function useManualAutoDj(): bool
    {
        return (bool)($this->get(self::USE_MANUAL_AUTODJ) ?? false);
    }

    public function setUseManualAutoDj(?bool $useManualAutoDj): void
    {
        $this->set(self::USE_MANUAL_AUTODJ, $useManualAutoDj);
    }

    public const AUTODJ_QUEUE_LENGTH = 'autodj_queue_length';
    public const DEFAULT_QUEUE_LENGTH = 3;

    public function getAutoDjQueueLength(): int
    {
        return (int)($this->get(self::AUTODJ_QUEUE_LENGTH) ?? self::DEFAULT_QUEUE_LENGTH);
    }

    public function setAutoDjQueueLength(?int $queueLength): void
    {
        $this->set(self::AUTODJ_QUEUE_LENGTH, $queueLength);
    }

    public const DJ_MOUNT_POINT = 'dj_mount_point';

    public function getDjMountPoint(): string
    {
        return $this->get(self::DJ_MOUNT_POINT) ?? '/';
    }

    public function setDjMountPoint(?string $mountPoint): void
    {
        $this->set(self::DJ_MOUNT_POINT, $mountPoint);
    }

    public const USE_NORMALIZER = 'nrj';

    public function useNormalizer(): bool
    {
        return $this->get(self::USE_NORMALIZER) ?? false;
    }

    public function setUseNormalizer(?bool $useNormalizer): void
    {
        $this->set(self::USE_NORMALIZER, $useNormalizer);
    }

    public const USE_REPLAYGAIN = 'enable_replaygain_metadata';

    public function useReplayGain(): bool
    {
        return $this->get(self::USE_REPLAYGAIN) ?? false;
    }

    public function setUseReplayGain(?bool $useReplayGain): void
    {
        $this->set(self::USE_REPLAYGAIN, $useReplayGain);
    }

    public const CROSSFADE_TYPE = 'crossfade_type';

    public const CROSSFADE_NORMAL = 'normal';
    public const CROSSFADE_DISABLED = 'none';
    public const CROSSFADE_SMART = 'smart';

    public function getCrossfadeType(): string
    {
        return $this->get(self::CROSSFADE_TYPE) ?? self::CROSSFADE_NORMAL;
    }

    public function setCrossfadeType(string $crossfadeType): void
    {
        $this->set(self::CROSSFADE_TYPE, $crossfadeType);
    }

    public const CROSSFADE = 'crossfade';

    public const DEFAULT_CROSSFADE_DURATION = 2;

    public function getCrossfade(): float
    {
        return round((float)($this->get(self::CROSSFADE) ?? self::DEFAULT_CROSSFADE_DURATION), 1);
    }

    public function setCrossfade(?float $crossfade): void
    {
        $this->set(self::CROSSFADE, $crossfade);
    }

    public function getCrossfadeDuration(): float
    {
        $crossfade = $this->getCrossfade();
        $crossfadeType = $this->getCrossfadeType();

        if (self::CROSSFADE_DISABLED !== $crossfadeType && $crossfade > 0) {
            return round($crossfade * 1.5, 2);
        }

        return 0;
    }

    public function isCrossfadeEnabled(): bool
    {
        return $this->getCrossfadeDuration() > 0;
    }

    public const DUPLICATE_PREVENTION_TIME_RANGE = 'duplicate_prevention_time_range';

    public const DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE = 120;

    public function getDuplicatePreventionTimeRange(): int
    {
        return (int)(
            $this->get(self::DUPLICATE_PREVENTION_TIME_RANGE) ?? self::DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE
        );
    }

    public function setDuplicatePreventionTimeRange(?int $duplicatePreventionTimeRange): void
    {
        $this->set(self::DUPLICATE_PREVENTION_TIME_RANGE, $duplicatePreventionTimeRange);
    }

    public const PERFORMANCE_MODE = 'performance_mode';

    public function getPerformanceMode(): string
    {
        return $this->getPerformanceModeEnum()->value;
    }

    public function getPerformanceModeEnum(): StationBackendPerformanceModes
    {
        return StationBackendPerformanceModes::tryFrom($this->get(self::PERFORMANCE_MODE) ?? '')
            ?? StationBackendPerformanceModes::default();
    }

    public function setPerformanceMode(?string $performanceMode): void
    {
        $perfModeEnum = StationBackendPerformanceModes::tryFrom($performanceMode ?? '');
        if (null === $perfModeEnum) {
            $this->set(self::PERFORMANCE_MODE, null);
        } else {
            $this->set(self::PERFORMANCE_MODE, $perfModeEnum->value);
        }
    }
}
