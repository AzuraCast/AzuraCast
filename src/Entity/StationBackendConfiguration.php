<?php
namespace App\Entity;

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

    public function setRecordStreamsFormat(?string $format): void
    {
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
}