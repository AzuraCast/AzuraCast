<?php
namespace App\Entity;

use App\Collection;

class StationBackendConfiguration extends Collection
{
    public const CHARSET = 'charset';

    public function getCharset(): string
    {
        return $this->data[self::CHARSET] ?? 'UTF-8';
    }

    public function setCharset(?string $charset): void
    {
        $this->data[self::CHARSET] = $charset;
    }

    public const DJ_PORT = 'dj_port';

    public function getDjPort(): ?int
    {
        $port = $this->data[self::DJ_PORT];
        return is_numeric($port) ? (int)$port : null;
    }

    public function setDjPort(?int $port): void
    {
        $this->data[self::DJ_PORT] = $port;
    }

    public const TELNET_PORT = 'telnet_port';

    public function getTelnetPort(): ?int
    {
        $port = $this->data[self::TELNET_PORT];
        return is_numeric($port) ? (int)$port : null;
    }

    public function setTelnetPort(?int $port): void
    {
        $this->data[self::TELNET_PORT] = $port;
    }

    public const RECORD_STREAMS = 'record_streams';

    public function recordStreams(): bool
    {
        return (bool)($this->data[self::RECORD_STREAMS] ?? false);
    }

    public function setRecordStreams(?bool $recordStreams): void
    {
        $this->data[self::RECORD_STREAMS] = $recordStreams;
    }

    public const RECORD_STREAMS_FORMAT = 'record_streams_format';

    public function getRecordStreamsFormat(): ?string
    {
        return $this->data[self::RECORD_STREAMS_FORMAT];
    }

    public function setRecordStreamsFormat(?string $format): void
    {
        $this->data[self::RECORD_STREAMS_FORMAT] = $format;
    }

    public const USE_MANUAL_AUTODJ = 'use_manual_autodj';

    public function useManualAutoDj(): bool
    {
        return (bool)($this->data[self::USE_MANUAL_AUTODJ] ?? false);
    }

    public function setUseManualAutoDj(?bool $useManualAutoDj): void
    {
        $this->data[self::USE_MANUAL_AUTODJ] = $useManualAutoDj;
    }

    public const AUTODJ_QUEUE_LENGTH = 'autodj_queue_length';
    public const DEFAULT_QUEUE_LENGTH = 3;

    public function getAutoDjQueueLength(): int
    {
        return (int)($this->data[self::AUTODJ_QUEUE_LENGTH] ?? self::DEFAULT_QUEUE_LENGTH);
    }

    public function setAutoDjQueueLength(?int $queueLength): void
    {
        $this->data[self::AUTODJ_QUEUE_LENGTH] = $queueLength;
    }

    public const DJ_MOUNT_POINT = 'dj_mount_point';

    public function getDjMountPoint(): string
    {
        return $this->data[self::DJ_MOUNT_POINT] ?? '/';
    }

    public function setDjMountPoint(?string $mountPoint): void
    {
        $this->data[self::DJ_MOUNT_POINT] = $mountPoint;
    }

    public const USE_NORMALIZER = 'nrj';

    public function useNormalizer(): bool
    {
        return $this->data[self::USE_NORMALIZER] ?? false;
    }

    public function setUseNormalizer(?bool $useNormalizer): void
    {
        $this->data[self::USE_NORMALIZER] = $useNormalizer;
    }

    public const USE_REPLAYGAIN = 'enable_replaygain_metadata';

    public function useReplayGain(): bool
    {
        return $this->data[self::USE_REPLAYGAIN] ?? false;
    }

    public function setUseReplayGain(?bool $useReplayGain): void
    {
        $this->data[self::USE_REPLAYGAIN] = $useReplayGain;
    }
}