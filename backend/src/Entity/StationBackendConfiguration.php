<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Entity\Enums\StationBackendPerformanceModes;
use App\Radio\Backend\Liquidsoap\EncodingFormat;
use App\Radio\Enums\AudioProcessingMethods;
use App\Radio\Enums\CrossfadeModes;
use App\Radio\Enums\MasterMePresets;
use App\Radio\Enums\StreamFormats;
use App\Utilities\Types;
use LogicException;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "StationBackendConfiguration", type: "object")]
final class StationBackendConfiguration extends AbstractArrayEntity
{
    #[OA\Property]
    public string $charset = 'UTF-8' {
        set (?string $value) => Types::string($value, 'UTF-8', true);
    }

    #[OA\Property]
    public ?int $dj_port = null {
        set (int|string|null $value) => Types::intOrNull($value);
    }

    #[OA\Property]
    public ?int $telnet_port = null {
        set (int|string|null $value) => Types::intOrNull($value);
    }

    #[OA\Property]
    public bool $record_streams = false {
        set(string|bool $value) => Types::bool($value, false, true);
    }

    #[OA\Property]
    public string $record_streams_format = '' {
        set (string|StreamFormats|null $value) {
            if ($value instanceof StreamFormats) {
                $value = $value->value;
            } else {
                if ($value !== null) {
                    $value = strtolower($value);
                    if (null === StreamFormats::tryFrom($value)) {
                        $value = null;
                    }
                }
            }

            $this->record_streams_format = $value ?? '';
        }
    }

    public function getRecordStreamsFormatEnum(): StreamFormats
    {
        return StreamFormats::tryFrom($this->record_streams_format) ?? StreamFormats::Mp3;
    }

    #[OA\Property]
    public int $record_streams_bitrate = 128 {
        set (int|string|null $value) => Types::int($value, 128);
    }

    public function getRecordStreamsEncoding(): ?EncodingFormat
    {
        if (!$this->record_streams) {
            return null;
        }

        return new EncodingFormat(
            format: $this->getRecordStreamsFormatEnum(),
            bitrate: $this->record_streams_bitrate,
            subProfile: null
        );
    }

    #[OA\Property]
    public bool $use_manual_autodj = false {
        set (bool|null $value) => Types::bool($value);
    }

    protected const int DEFAULT_QUEUE_LENGTH = 3;

    #[OA\Property]
    public int $autodj_queue_length = self::DEFAULT_QUEUE_LENGTH {
        set(int|string|null $value) => Types::int($value, self::DEFAULT_QUEUE_LENGTH);
    }

    #[OA\Property]
    public string $dj_mount_point = '/' {
        set (string|null $value) => Types::string($value, '/', true);
    }

    protected const int DEFAULT_DJ_BUFFER = 5;

    #[OA\Property]
    public int $dj_buffer = self::DEFAULT_DJ_BUFFER {
        set (int|string|null $value) => Types::int($value, self::DEFAULT_DJ_BUFFER);
    }

    #[OA\Property]
    public string $audio_processing_method = '' {
        set(string|AudioProcessingMethods|null $value) {
            if ($value instanceof AudioProcessingMethods) {
                $value = $value->value;
            } else {
                if ($value !== null) {
                    $value = strtolower($value);
                    if (null === AudioProcessingMethods::tryFrom($value)) {
                        $value = null;
                    }
                }
            }

            $this->audio_processing_method = $value ?? '';
        }
    }

    public function getAudioProcessingMethodEnum(): AudioProcessingMethods
    {
        return AudioProcessingMethods::tryFrom($this->audio_processing_method)
            ?? AudioProcessingMethods::default();
    }

    public function isAudioProcessingEnabled(): bool
    {
        return AudioProcessingMethods::None !== $this->getAudioProcessingMethodEnum();
    }

    #[OA\Property]
    public bool $post_processing_include_live = false {
        set (bool|string|null $value) => Types::bool($value);
    }

    #[OA\Property]
    public ?string $stereo_tool_license_key = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $stereo_tool_configuration_path = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $master_me_preset = null {
        set (string|MasterMePresets|null $value) {
            if ($value instanceof MasterMePresets) {
                $value = $value->value;
            } elseif ($value !== null) {
                $value = strtolower($value);

                if (null === MasterMePresets::tryFrom($value)) {
                    $value = null;
                }
            }

            $this->master_me_preset = $value;
        }
    }

    public function getMasterMePresetEnum(): MasterMePresets
    {
        return MasterMePresets::tryFrom($this->master_me_preset ?? '')
            ?? MasterMePresets::default();
    }

    protected const int MASTER_ME_DEFAULT_LOUDNESS_TARGET = -16;

    #[OA\Property]
    public int $master_me_loudness_target = self::MASTER_ME_DEFAULT_LOUDNESS_TARGET {
        set (int|string|null $value) => Types::int($value, self::MASTER_ME_DEFAULT_LOUDNESS_TARGET);
    }

    #[OA\Property]
    public bool $enable_replaygain_metadata = false {
        get => ($this->enable_auto_cue) ? false : $this->enable_replaygain_metadata;
        set (bool|string|null $value) => Types::bool($value, false, true);
    }

    #[OA\Property]
    public string $crossfade_type = '';

    public function getCrossfadeTypeEnum(): CrossfadeModes
    {
        // AutoCue overrides this functionality.
        if ($this->enable_auto_cue) {
            return CrossfadeModes::Disabled;
        }

        return CrossfadeModes::tryFrom($this->crossfade_type) ?? CrossfadeModes::default();
    }

    protected const float DEFAULT_CROSSFADE_DURATION = 2.0;

    #[OA\Property]
    public float $crossfade = self::DEFAULT_CROSSFADE_DURATION {
        set (float|string|null $value) => round(
            Types::float($value, self::DEFAULT_CROSSFADE_DURATION),
            1
        );
    }

    public function getCrossfadeDuration(): float
    {
        $crossfade = $this->crossfade;
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

    protected const int DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE = 120;

    #[OA\Property]
    public int $duplicate_prevention_time_range = self::DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE {
        set (int|string|null $value) => Types::int($value, self::DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE);
    }

    #[OA\Property]
    public string $performance_mode = '' {
        set(string|StationBackendPerformanceModes|null $value) {
            if ($value instanceof StationBackendPerformanceModes) {
                $value = $value->value;
            } else {
                if ($value !== null) {
                    if (null === StationBackendPerformanceModes::tryFrom($value)) {
                        $value = null;
                    }
                }
            }

            $this->performance_mode = $value ?? '';
        }
    }

    public function getPerformanceModeEnum(): StationBackendPerformanceModes
    {
        return StationBackendPerformanceModes::tryFrom($this->performance_mode)
            ?? StationBackendPerformanceModes::default();
    }

    #[OA\Property]
    public int $hls_segment_length = 4 {
        set (int|string|null $value) => Types::int($value, 4);
    }

    #[OA\Property]
    public int $hls_segments_in_playlist = 5 {
        set (int|string|null $value) => Types::int($value, 5);
    }

    #[OA\Property]
    public int $hls_segments_overhead = 2 {
        set (int|string|null $value) => Types::int($value, 2);
    }

    #[OA\Property]
    public bool $hls_enable_on_public_player = false {
        set (bool|string|null $value) => Types::bool($value, false, true);
    }

    #[OA\Property]
    public bool $hls_is_default = false {
        set (bool|string|null $value) => Types::bool($value, false, true);
    }

    #[OA\Property]
    public string $live_broadcast_text = 'Live Broadcast' {
        set (string|null $value) => Types::string($value, 'Live Broadcast');
    }

    #[OA\Property]
    public bool $enable_auto_cue = false;

    #[OA\Property]
    public bool $write_playlists_to_liquidsoap = false;

    #[OA\Property]
    public bool $share_encoders = false;

    /*
     * Liquidsoap Custom Configuration Sections
     */

    public const string CUSTOM_TOP = 'custom_config_top';

    #[OA\Property(
        description: 'Custom Liquidsoap Configuration: Top Section'
    )]
    public ?string $custom_config_top = null;

    public const string CUSTOM_PRE_PLAYLISTS = 'custom_config_pre_playlists';

    #[OA\Property(
        description: 'Custom Liquidsoap Configuration: Pre-Playlists Section'
    )]
    public ?string $custom_config_pre_playlists = null;

    public const string CUSTOM_PRE_LIVE = 'custom_config_pre_live';

    #[OA\Property(
        description: 'Custom Liquidsoap Configuration: Pre-Live Section'
    )]
    public ?string $custom_config_pre_live = null;

    public const string CUSTOM_PRE_FADE = 'custom_config_pre_fade';

    #[OA\Property(
        description: 'Custom Liquidsoap Configuration: Pre-Fade Section'
    )]
    public ?string $custom_config_pre_fade = null;

    public const string CUSTOM_PRE_BROADCAST = 'custom_config';

    #[OA\Property(
        description: 'Custom Liquidsoap Configuration: Pre-Broadcast Section'
    )]
    public ?string $custom_config = null;

    public const string CUSTOM_BOTTOM = 'custom_config_bottom';

    #[OA\Property(
        description: 'Custom Liquidsoap Configuration: Post-Broadcast Section'
    )]
    public ?string $custom_config_bottom = null;

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

        return $this->$section;
    }

    public function setCustomConfigurationSection(string $section, ?string $value = null): void
    {
        $allSections = self::getCustomConfigurationSections();
        if (!in_array($section, $allSections, true)) {
            throw new LogicException('Invalid custom configuration section.');
        }

        $this->$section = $value;
    }
}
