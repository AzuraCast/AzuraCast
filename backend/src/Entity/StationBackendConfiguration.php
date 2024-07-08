<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\StationBackendPerformanceModes;
use App\Radio\Enums\AudioProcessingMethods;
use App\Radio\Enums\CrossfadeModes;
use App\Radio\Enums\MasterMePresets;
use App\Radio\Enums\StreamFormats;
use App\Utilities\Types;
use LogicException;

class StationBackendConfiguration extends AbstractStationConfiguration
{
    public const string CHARSET = 'charset';

    public function getCharset(): string
    {
        return Types::stringOrNull($this->get(self::CHARSET), true) ?? 'UTF-8';
    }

    public function setCharset(?string $charset): void
    {
        $this->set(self::CHARSET, $charset);
    }

    public const string DJ_PORT = 'dj_port';

    public function getDjPort(): ?int
    {
        return Types::intOrNull($this->get(self::DJ_PORT));
    }

    public function setDjPort(int|string $port = null): void
    {
        $this->set(self::DJ_PORT, $port);
    }

    public const string TELNET_PORT = 'telnet_port';

    public function getTelnetPort(): ?int
    {
        return Types::intOrNull($this->get(self::TELNET_PORT));
    }

    public function setTelnetPort(int|string $port = null): void
    {
        $this->set(self::TELNET_PORT, $port);
    }

    public const string RECORD_STREAMS = 'record_streams';

    public function recordStreams(): bool
    {
        return Types::boolOrNull($this->get(self::RECORD_STREAMS)) ?? false;
    }

    public function setRecordStreams(string|bool $recordStreams): void
    {
        $this->set(self::RECORD_STREAMS, $recordStreams);
    }

    public const string RECORD_STREAMS_FORMAT = 'record_streams_format';

    public function getRecordStreamsFormat(): string
    {
        return $this->getRecordStreamsFormatEnum()->value;
    }

    public function getRecordStreamsFormatEnum(): StreamFormats
    {
        return StreamFormats::tryFrom(
            Types::stringOrNull($this->get(self::RECORD_STREAMS_FORMAT)) ?? ''
        )
            ?? StreamFormats::Mp3;
    }

    public function setRecordStreamsFormat(?string $format): void
    {
        if (null !== $format) {
            $format = strtolower($format);

            if (null === StreamFormats::tryFrom($format)) {
                $format = null;
            }
        }

        $this->set(self::RECORD_STREAMS_FORMAT, $format);
    }

    public const string RECORD_STREAMS_BITRATE = 'record_streams_bitrate';

    public function getRecordStreamsBitrate(): int
    {
        return Types::intOrNull($this->get(self::RECORD_STREAMS_BITRATE)) ?? 128;
    }

    public function setRecordStreamsBitrate(int|string $bitrate = null): void
    {
        $this->set(self::RECORD_STREAMS_BITRATE, $bitrate);
    }

    public const string USE_MANUAL_AUTODJ = 'use_manual_autodj';

    public function useManualAutoDj(): bool
    {
        return Types::boolOrNull($this->get(self::USE_MANUAL_AUTODJ)) ?? false;
    }

    public function setUseManualAutoDj(?bool $useManualAutoDj): void
    {
        $this->set(self::USE_MANUAL_AUTODJ, $useManualAutoDj);
    }

    public const string AUTODJ_QUEUE_LENGTH = 'autodj_queue_length';

    protected const int DEFAULT_QUEUE_LENGTH = 3;

    public function getAutoDjQueueLength(): int
    {
        return Types::intOrNull($this->get(self::AUTODJ_QUEUE_LENGTH)) ?? self::DEFAULT_QUEUE_LENGTH;
    }

    public function setAutoDjQueueLength(int|string $queueLength = null): void
    {
        $this->set(self::AUTODJ_QUEUE_LENGTH, $queueLength);
    }

    public const string DJ_MOUNT_POINT = 'dj_mount_point';

    public function getDjMountPoint(): string
    {
        return Types::stringOrNull($this->get(self::DJ_MOUNT_POINT)) ?? '/';
    }

    public function setDjMountPoint(?string $mountPoint): void
    {
        $this->set(self::DJ_MOUNT_POINT, $mountPoint);
    }

    public const string DJ_BUFFER = 'dj_buffer';

    protected const int DEFAULT_DJ_BUFFER = 5;

    public function getDjBuffer(): int
    {
        return Types::intOrNull($this->get(self::DJ_BUFFER)) ?? self::DEFAULT_DJ_BUFFER;
    }

    public function setDjBuffer(int|string $buffer = null): void
    {
        $this->set(self::DJ_BUFFER, $buffer);
    }

    public const string AUDIO_PROCESSING_METHOD = 'audio_processing_method';

    public function getAudioProcessingMethod(): ?string
    {
        return $this->getAudioProcessingMethodEnum()->value;
    }

    public function getAudioProcessingMethodEnum(): AudioProcessingMethods
    {
        return AudioProcessingMethods::tryFrom(
            Types::stringOrNull($this->get(self::AUDIO_PROCESSING_METHOD)) ?? ''
        ) ?? AudioProcessingMethods::default();
    }

    public function isAudioProcessingEnabled(): bool
    {
        return AudioProcessingMethods::None !== $this->getAudioProcessingMethodEnum();
    }

    public function setAudioProcessingMethod(?string $method): void
    {
        if (null !== $method) {
            $method = strtolower($method);

            if (null === AudioProcessingMethods::tryFrom($method)) {
                $method = null;
            }
        }

        $this->set(self::AUDIO_PROCESSING_METHOD, $method);
    }

    public const string POST_PROCESSING_INCLUDE_LIVE = 'post_processing_include_live';

    public function getPostProcessingIncludeLive(): bool
    {
        return Types::boolOrNull($this->get(self::POST_PROCESSING_INCLUDE_LIVE)) ?? false;
    }

    public function setPostProcessingIncludeLive(bool|string $postProcessingIncludeLive = null): void
    {
        $this->set(self::POST_PROCESSING_INCLUDE_LIVE, $postProcessingIncludeLive);
    }

    public const string STEREO_TOOL_LICENSE_KEY = 'stereo_tool_license_key';

    public function getStereoToolLicenseKey(): ?string
    {
        return Types::stringOrNull($this->get(self::STEREO_TOOL_LICENSE_KEY), true);
    }

    public function setStereoToolLicenseKey(?string $licenseKey): void
    {
        $this->set(self::STEREO_TOOL_LICENSE_KEY, $licenseKey);
    }

    public const string STEREO_TOOL_CONFIGURATION_PATH = 'stereo_tool_configuration_path';

    public function getStereoToolConfigurationPath(): ?string
    {
        return Types::stringOrNull($this->get(self::STEREO_TOOL_CONFIGURATION_PATH), true);
    }

    public function setStereoToolConfigurationPath(?string $stereoToolConfigurationPath): void
    {
        $this->set(self::STEREO_TOOL_CONFIGURATION_PATH, $stereoToolConfigurationPath);
    }

    public const string MASTER_ME_PRESET = 'master_me_preset';

    public function getMasterMePreset(): ?string
    {
        return Types::stringOrNull($this->get(self::MASTER_ME_PRESET), true);
    }

    public function getMasterMePresetEnum(): MasterMePresets
    {
        return MasterMePresets::tryFrom($this->getMasterMePreset() ?? '')
            ?? MasterMePresets::default();
    }

    public function setMasterMePreset(?string $masterMePreset): void
    {
        if (null !== $masterMePreset) {
            $masterMePreset = strtolower($masterMePreset);

            if (null === MasterMePresets::tryFrom($masterMePreset)) {
                $masterMePreset = null;
            }
        }

        $this->set(self::MASTER_ME_PRESET, $masterMePreset);
    }

    public const string MASTER_ME_LOUDNESS_TARGET = 'master_me_loudness_target';

    protected const int MASTER_ME_DEFAULT_LOUDNESS_TARGET = -16;

    public function getMasterMeLoudnessTarget(): int
    {
        return Types::intOrNull($this->get(self::MASTER_ME_LOUDNESS_TARGET))
            ?? self::MASTER_ME_DEFAULT_LOUDNESS_TARGET;
    }

    public function setMasterMeLoudnessTarget(int|string $masterMeLoudnessTarget = null): void
    {
        $this->set(self::MASTER_ME_LOUDNESS_TARGET, $masterMeLoudnessTarget);
    }

    public const string USE_REPLAYGAIN = 'enable_replaygain_metadata';

    public function useReplayGain(): bool
    {
        // AutoCue overrides this functionality.
        if ($this->getEnableAutoCue()) {
            return false;
        }

        return Types::boolOrNull($this->get(self::USE_REPLAYGAIN)) ?? false;
    }

    public function setUseReplayGain(bool|string $useReplayGain): void
    {
        $this->set(self::USE_REPLAYGAIN, $useReplayGain);
    }

    public const string CROSSFADE_TYPE = 'crossfade_type';

    public function getCrossfadeTypeEnum(): CrossfadeModes
    {
        // AutoCue overrides this functionality.
        if ($this->getEnableAutoCue()) {
            return CrossfadeModes::Disabled;
        }

        return CrossfadeModes::tryFrom(
            Types::stringOrNull($this->get(self::CROSSFADE_TYPE)) ?? ''
        ) ?? CrossfadeModes::default();
    }

    public function getCrossfadeType(): string
    {
        return $this->getCrossfadeTypeEnum()->value;
    }

    public function setCrossfadeType(string $crossfadeType): void
    {
        $this->set(self::CROSSFADE_TYPE, $crossfadeType);
    }

    public const string CROSSFADE = 'crossfade';

    protected const int DEFAULT_CROSSFADE_DURATION = 2;

    public function getCrossfade(): float
    {
        return round(
            Types::floatOrNull($this->get(self::CROSSFADE)) ?? self::DEFAULT_CROSSFADE_DURATION,
            1
        );
    }

    public function setCrossfade(float|string $crossfade = null): void
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

    public const string DUPLICATE_PREVENTION_TIME_RANGE = 'duplicate_prevention_time_range';

    protected const int DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE = 120;

    public function getDuplicatePreventionTimeRange(): int
    {
        return Types::intOrNull($this->get(self::DUPLICATE_PREVENTION_TIME_RANGE))
            ?? self::DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE;
    }

    public function setDuplicatePreventionTimeRange(int|string $duplicatePreventionTimeRange = null): void
    {
        $this->set(self::DUPLICATE_PREVENTION_TIME_RANGE, $duplicatePreventionTimeRange);
    }

    public const string PERFORMANCE_MODE = 'performance_mode';

    public function getPerformanceMode(): string
    {
        return $this->getPerformanceModeEnum()->value;
    }

    public function getPerformanceModeEnum(): StationBackendPerformanceModes
    {
        return StationBackendPerformanceModes::tryFrom(
            Types::stringOrNull($this->get(self::PERFORMANCE_MODE)) ?? ''
        ) ?? StationBackendPerformanceModes::default();
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

    public const string HLS_SEGMENT_LENGTH = 'hls_segment_length';

    public function getHlsSegmentLength(): int
    {
        return Types::intOrNull($this->get(self::HLS_SEGMENT_LENGTH)) ?? 4;
    }

    public function setHlsSegmentLength(int|string $length = null): void
    {
        $this->set(self::HLS_SEGMENT_LENGTH, $length);
    }

    public const string HLS_SEGMENTS_IN_PLAYLIST = 'hls_segments_in_playlist';

    public function getHlsSegmentsInPlaylist(): int
    {
        return Types::intOrNull($this->get(self::HLS_SEGMENTS_IN_PLAYLIST)) ?? 5;
    }

    public function setHlsSegmentsInPlaylist(int|string $value = null): void
    {
        $this->set(self::HLS_SEGMENTS_IN_PLAYLIST, $value);
    }

    public const string HLS_SEGMENTS_OVERHEAD = 'hls_segments_overhead';

    public function getHlsSegmentsOverhead(): int
    {
        return Types::intOrNull($this->get(self::HLS_SEGMENTS_OVERHEAD)) ?? 2;
    }

    public function setHlsSegmentsOverhead(int|string $value = null): void
    {
        $this->set(self::HLS_SEGMENTS_OVERHEAD, $value);
    }

    public const string HLS_ENABLE_ON_PUBLIC_PLAYER = 'hls_enable_on_public_player';

    public function getHlsEnableOnPublicPlayer(): bool
    {
        return Types::boolOrNull($this->get(self::HLS_ENABLE_ON_PUBLIC_PLAYER)) ?? false;
    }

    public function setHlsEnableOnPublicPlayer(bool|string $enable): void
    {
        $this->set(self::HLS_ENABLE_ON_PUBLIC_PLAYER, $enable);
    }

    public const string HLS_IS_DEFAULT = 'hls_is_default';

    public function getHlsIsDefault(): bool
    {
        return Types::boolOrNull($this->get(self::HLS_IS_DEFAULT)) ?? false;
    }

    public function setHlsIsDefault(bool|string $value): void
    {
        $this->set(self::HLS_IS_DEFAULT, $value);
    }

    public const string LIVE_BROADCAST_TEXT = 'live_broadcast_text';

    public function getLiveBroadcastText(): string
    {
        return Types::stringOrNull($this->get(self::LIVE_BROADCAST_TEXT), true)
            ?? 'Live Broadcast';
    }

    public function setLiveBroadcastText(?string $text): void
    {
        $this->set(self::LIVE_BROADCAST_TEXT, $text);
    }

    public const string ENABLE_AUTO_CUE = 'enable_auto_cue';

    public function getEnableAutoCue(): bool
    {
        return Types::bool($this->get(self::ENABLE_AUTO_CUE));
    }

    public function setEnableAutoCue(bool $value): void
    {
        $this->set(self::ENABLE_AUTO_CUE, $value);
    }

    public const string WRITE_PLAYLISTS_TO_LIQUIDSOAP = 'write_playlists_to_liquidsoap';

    public function getWritePlaylistsToLiquidsoap(): bool
    {
        return Types::bool($this->get(self::WRITE_PLAYLISTS_TO_LIQUIDSOAP), true);
    }

    public function setWritePlaylistsToLiquidsoap(bool $value): void
    {
        $this->set(self::WRITE_PLAYLISTS_TO_LIQUIDSOAP, $value);
    }

    public const string CUSTOM_TOP = 'custom_config_top';
    public const string CUSTOM_PRE_PLAYLISTS = 'custom_config_pre_playlists';
    public const string CUSTOM_PRE_LIVE = 'custom_config_pre_live';
    public const string CUSTOM_PRE_FADE = 'custom_config_pre_fade';
    public const string CUSTOM_PRE_BROADCAST = 'custom_config';
    public const string CUSTOM_BOTTOM = 'custom_config_bottom';

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

        return Types::stringOrNull($this->get($section), true);
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
