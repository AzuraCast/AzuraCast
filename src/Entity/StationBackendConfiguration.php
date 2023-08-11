<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\StationBackendPerformanceModes;
use App\Radio\Enums\AudioProcessingMethods;
use App\Radio\Enums\CrossfadeModes;
use App\Radio\Enums\MasterMePresets;
use App\Radio\Enums\StreamFormats;
use InvalidArgumentException;
use LogicException;

class StationBackendConfiguration extends AbstractStationConfiguration
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

    public function getRecordStreamsFormat(): string
    {
        return $this->getRecordStreamsFormatEnum()->value;
    }

    public function getRecordStreamsFormatEnum(): StreamFormats
    {
        return StreamFormats::tryFrom($this->get(self::RECORD_STREAMS_FORMAT) ?? '')
            ?? StreamFormats::Mp3;
    }

    public function setRecordStreamsFormat(?string $format): void
    {
        if (null !== $format) {
            $format = strtolower($format);
        }

        if (null !== $format && null === StreamFormats::tryFrom($format)) {
            throw new InvalidArgumentException('Invalid recording type specified.');
        }

        $this->set(self::RECORD_STREAMS_FORMAT, $format);
    }

    public const RECORD_STREAMS_BITRATE = 'record_streams_bitrate';

    public function getRecordStreamsBitrate(): int
    {
        return (int)($this->get(self::RECORD_STREAMS_BITRATE) ?? 128);
    }

    public function setRecordStreamsBitrate(?int $bitrate): void
    {
        $this->set(self::RECORD_STREAMS_BITRATE, $bitrate);
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

    protected const DEFAULT_QUEUE_LENGTH = 3;

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

    public const DJ_BUFFER = 'dj_buffer';

    protected const DEFAULT_DJ_BUFFER = 5;

    public function getDjBuffer(): int
    {
        return (int)$this->get(self::DJ_BUFFER, self::DEFAULT_DJ_BUFFER);
    }

    public function setDjBuffer(?int $buffer): void
    {
        $this->set(self::DJ_BUFFER, $buffer);
    }

    public const AUDIO_PROCESSING_METHOD = 'audio_processing_method';

    public function getAudioProcessingMethod(): ?string
    {
        return $this->getAudioProcessingMethodEnum()->value;
    }

    public function getAudioProcessingMethodEnum(): AudioProcessingMethods
    {
        return AudioProcessingMethods::tryFrom($this->get(self::AUDIO_PROCESSING_METHOD) ?? '')
            ?? AudioProcessingMethods::default();
    }

    public function isAudioProcessingEnabled(): bool
    {
        return AudioProcessingMethods::None !== $this->getAudioProcessingMethodEnum();
    }

    public function setAudioProcessingMethod(?string $method): void
    {
        if (null !== $method) {
            $method = strtolower($method);
        }

        if (null !== $method && null === AudioProcessingMethods::tryFrom($method)) {
            throw new InvalidArgumentException('Invalid audio processing method specified.');
        }

        $this->set(self::AUDIO_PROCESSING_METHOD, $method);
    }

    public const POST_PROCESSING_INCLUDE_LIVE = 'post_processing_include_live';

    public function getPostProcessingIncludeLive(): bool
    {
        return $this->get(self::POST_PROCESSING_INCLUDE_LIVE, false);
    }

    public function setPostProcessingIncludeLive(bool $postProcessingIncludeLive): void
    {
        $this->set(self::POST_PROCESSING_INCLUDE_LIVE, $postProcessingIncludeLive);
    }

    public const STEREO_TOOL_LICENSE_KEY = 'stereo_tool_license_key';

    public function getStereoToolLicenseKey(): ?string
    {
        return $this->get(self::STEREO_TOOL_LICENSE_KEY);
    }

    public function setStereoToolLicenseKey(?string $licenseKey): void
    {
        $this->set(self::STEREO_TOOL_LICENSE_KEY, $licenseKey);
    }

    public const STEREO_TOOL_CONFIGURATION_PATH = 'stereo_tool_configuration_path';

    public function getStereoToolConfigurationPath(): ?string
    {
        return $this->get(self::STEREO_TOOL_CONFIGURATION_PATH);
    }

    public function setStereoToolConfigurationPath(?string $stereoToolConfigurationPath): void
    {
        $this->set(self::STEREO_TOOL_CONFIGURATION_PATH, $stereoToolConfigurationPath);
    }

    public const MASTER_ME_PRESET = 'master_me_preset';

    public function getMasterMePreset(): ?string
    {
        return $this->get(self::MASTER_ME_PRESET);
    }

    public function getMasterMePresetEnum(): MasterMePresets
    {
        return MasterMePresets::tryFrom($this->get(self::MASTER_ME_PRESET) ?? '')
            ?? MasterMePresets::default();
    }

    public function setMasterMePreset(?string $masterMePreset): void
    {
        if (null !== $masterMePreset) {
            $masterMePreset = strtolower($masterMePreset);
        }

        if (null !== $masterMePreset && null === MasterMePresets::tryFrom($masterMePreset)) {
            throw new InvalidArgumentException('Invalid master_me preset specified.');
        }

        $this->set(self::MASTER_ME_PRESET, $masterMePreset);
    }

    public const MASTER_ME_LOUDNESS_TARGET = 'master_me_loudness_target';

    protected const MASTER_ME_DEFAULT_LOUDNESS_TARGET = -16.0;

    public function getMasterMeLoudnessTarget(): float
    {
        return (float)$this->get(self::MASTER_ME_LOUDNESS_TARGET, self::MASTER_ME_DEFAULT_LOUDNESS_TARGET);
    }

    public function setMasterMeLoudnessTarget(?float $masterMeLoudnessTarget): void
    {
        $this->set(self::MASTER_ME_LOUDNESS_TARGET, $masterMeLoudnessTarget);
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

    public function getCrossfadeTypeEnum(): CrossfadeModes
    {
        return CrossfadeModes::tryFrom($this->get(self::CROSSFADE_TYPE) ?? '')
            ?? CrossfadeModes::default();
    }

    public function getCrossfadeType(): string
    {
        return $this->getCrossfadeTypeEnum()->value;
    }

    public function setCrossfadeType(string $crossfadeType): void
    {
        $this->set(self::CROSSFADE_TYPE, $crossfadeType);
    }

    public const CROSSFADE = 'crossfade';

    protected const DEFAULT_CROSSFADE_DURATION = 2;

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
        $crossfadeType = $this->getCrossfadeTypeEnum();

        if (CrossfadeModes::Disabled !== $crossfadeType && $crossfade > 0) {
            return round($crossfade * 1.5, 2);
        }

        return 0;
    }

    public function isCrossfadeEnabled(): bool
    {
        return $this->getCrossfadeDuration() > 0;
    }

    public const DUPLICATE_PREVENTION_TIME_RANGE = 'duplicate_prevention_time_range';

    protected const DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE = 120;

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

    public const HLS_SEGMENT_LENGTH = 'hls_segment_length';

    public function getHlsSegmentLength(): int
    {
        return $this->get(self::HLS_SEGMENT_LENGTH, 4);
    }

    public function setHlsSegmentLength(?int $length): void
    {
        $this->set(self::HLS_SEGMENT_LENGTH, $length);
    }

    public const HLS_SEGMENTS_IN_PLAYLIST = 'hls_segments_in_playlist';

    public function getHlsSegmentsInPlaylist(): int
    {
        return $this->get(self::HLS_SEGMENTS_IN_PLAYLIST, 5);
    }

    public function setHlsSegmentsInPlaylist(?int $value): void
    {
        $this->set(self::HLS_SEGMENTS_IN_PLAYLIST, $value);
    }

    public const HLS_SEGMENTS_OVERHEAD = 'hls_segments_overhead';

    public function getHlsSegmentsOverhead(): int
    {
        return $this->get(self::HLS_SEGMENTS_OVERHEAD, 2);
    }

    public function setHlsSegmentsOverhead(?int $value): void
    {
        $this->set(self::HLS_SEGMENTS_OVERHEAD, $value);
    }

    public const HLS_ENABLE_ON_PUBLIC_PLAYER = 'hls_enable_on_public_player';

    public function getHlsEnableOnPublicPlayer(): bool
    {
        return $this->get(self::HLS_ENABLE_ON_PUBLIC_PLAYER, false);
    }

    public function setHlsEnableOnPublicPlayer(?bool $enable): void
    {
        $this->set(self::HLS_ENABLE_ON_PUBLIC_PLAYER, $enable);
    }

    public const HLS_IS_DEFAULT = 'hls_is_default';

    public function getHlsIsDefault(): bool
    {
        return $this->get(self::HLS_IS_DEFAULT, false);
    }

    public function setHlsIsDefault(?bool $value): void
    {
        $this->set(self::HLS_IS_DEFAULT, $value);
    }

    public const LIVE_BROADCAST_TEXT = 'live_broadcast_text';

    public function getLiveBroadcastText(): string
    {
        $text = $this->get(self::LIVE_BROADCAST_TEXT);

        return (!empty($text))
            ? $text
            : 'Live Broadcast';
    }

    public function setLiveBroadcastText(?string $text): void
    {
        $this->set(self::LIVE_BROADCAST_TEXT, $text);
    }

    public const CUSTOM_TOP = 'custom_config_top';
    public const CUSTOM_PRE_PLAYLISTS = 'custom_config_pre_playlists';
    public const CUSTOM_PRE_LIVE = 'custom_config_pre_live';
    public const CUSTOM_PRE_FADE = 'custom_config_pre_fade';
    public const CUSTOM_PRE_BROADCAST = 'custom_config';
    public const CUSTOM_BOTTOM = 'custom_config_bottom';

    /** @return array<int, string> */
    public static function getCustomConfigurationSections(): array
    {
        return [
            self::CUSTOM_TOP,
            self::CUSTOM_PRE_PLAYLISTS,
            self::CUSTOM_PRE_FADE,
            self::CUSTOM_PRE_LIVE,
            self::CUSTOM_PRE_BROADCAST,
            self::CUSTOM_BOTTOM,
        ];
    }

    public function getCustomConfigurationSection(string $section): ?string
    {
        $allSections = self::getCustomConfigurationSections();
        if (!in_array($section, $allSections, true)) {
            throw new LogicException('Invalid custom configuration section.');
        }

        return $this->get($section);
    }

    public function setCustomConfigurationSection(string $section, ?string $value = null): void
    {
        $allSections = self::getCustomConfigurationSections();
        if (!in_array($section, $allSections, true)) {
            throw new LogicException('Invalid custom configuration section.');
        }

        $this->set($section, $value);
    }
}
