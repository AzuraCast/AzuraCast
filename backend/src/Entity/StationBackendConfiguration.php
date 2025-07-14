<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Entity\Enums\StationBackendPerformanceModes;
use App\Radio\Enums\AudioProcessingMethods;
use App\Radio\Enums\CrossfadeModes;
use App\Radio\Enums\MasterMePresets;
use App\Radio\Enums\StreamFormats;
use App\Utilities\Types;
use LogicException;

class StationBackendConfiguration extends AbstractArrayEntity
{
    public string $charset {
        get => Types::stringOrNull($this->get(__PROPERTY__), true) ?? 'UTF-8';
        set (?string $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?int $dj_port {
        get => Types::intOrNull($this->get(__PROPERTY__));
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?int $telnet_port {
        get => Types::intOrNull($this->get(__PROPERTY__));
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $record_streams {
        get => Types::boolOrNull($this->get(__PROPERTY__)) ?? false;
        set(string|bool $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $record_streams_format {
        get => Types::stringOrNull($this->get(__PROPERTY__)) ?? '';
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

            $this->set(__PROPERTY__, $value);
        }
    }

    public function getRecordStreamsFormatEnum(): StreamFormats
    {
        return StreamFormats::tryFrom($this->record_streams_format) ?? StreamFormats::Mp3;
    }

    public int $record_streams_bitrate {
        get => Types::intOrNull($this->get(__PROPERTY__)) ?? 128;
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $use_manual_autodj {
        get => Types::boolOrNull($this->get(__PROPERTY__)) ?? false;
        set (bool|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    protected const int DEFAULT_QUEUE_LENGTH = 3;

    public int $autodj_queue_length {
        get => Types::intOrNull($this->get(__PROPERTY__)) ?? self::DEFAULT_QUEUE_LENGTH;
        set(int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $dj_mount_point {
        get => Types::stringOrNull($this->get(__PROPERTY__)) ?? '/';
        set (string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    protected const int DEFAULT_DJ_BUFFER = 5;

    public int $dj_buffer {
        get => Types::intOrNull($this->get(__PROPERTY__)) ?? self::DEFAULT_DJ_BUFFER;
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $audio_processing_method {
        get => Types::string($this->get(__PROPERTY__));
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

            $this->set(__PROPERTY__, $value);
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

    public bool $post_processing_include_live {
        get => Types::bool($this->get(__PROPERTY__));
        set (bool|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $stereo_tool_license_key {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $stereo_tool_configuration_path {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $master_me_preset {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set (string|MasterMePresets|null $value) {
            if ($value instanceof MasterMePresets) {
                $value = $value->value;
            } elseif ($value !== null) {
                $value = strtolower($value);

                if (null === MasterMePresets::tryFrom($value)) {
                    $value = null;
                }
            }

            $this->set(__PROPERTY__, $value);
        }
    }

    public function getMasterMePresetEnum(): MasterMePresets
    {
        return MasterMePresets::tryFrom($this->master_me_preset ?? '')
            ?? MasterMePresets::default();
    }

    protected const int MASTER_ME_DEFAULT_LOUDNESS_TARGET = -16;

    public int $master_me_loudness_target {
        get => Types::intOrNull($this->get(__PROPERTY__))
            ?? self::MASTER_ME_DEFAULT_LOUDNESS_TARGET;
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $enable_replaygain_metadata {
        get {
            // AutoCue overrides this functionality.
            if ($this->enable_auto_cue) {
                return false;
            }

            return Types::bool($this->get(__PROPERTY__));
        }
        set (bool|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $crossfade_type {
        get => Types::string($this->get(__PROPERTY__));
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public function getCrossfadeTypeEnum(): CrossfadeModes
    {
        // AutoCue overrides this functionality.
        if ($this->enable_auto_cue) {
            return CrossfadeModes::Disabled;
        }

        return CrossfadeModes::tryFrom($this->crossfade_type) ?? CrossfadeModes::default();
    }

    protected const int DEFAULT_CROSSFADE_DURATION = 2;

    public float $crossfade {
        get => round(
            Types::floatOrNull($this->get(__PROPERTY__)) ?? self::DEFAULT_CROSSFADE_DURATION,
            1
        );
        set (float|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
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

    public int $duplicate_prevention_time_range {
        get => Types::int($this->get(__PROPERTY__), self::DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE);
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $performance_mode {
        get => Types::string($this->get(__PROPERTY__));
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

            $this->set(__PROPERTY__, $value);
        }
    }

    public function getPerformanceModeEnum(): StationBackendPerformanceModes
    {
        return StationBackendPerformanceModes::tryFrom($this->performance_mode)
            ?? StationBackendPerformanceModes::default();
    }

    public int $hls_segment_length {
        get => Types::int($this->get(__PROPERTY__), 4);
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public int $hls_segments_in_playlist {
        get => Types::int($this->get(__PROPERTY__), 5);
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public int $hls_segments_overhead {
        get => Types::int($this->get(__PROPERTY__), 2);
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $hls_enable_on_public_player {
        get => Types::bool($this->get(__PROPERTY__));
        set (bool|string $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $hls_is_default {
        get => Types::bool($this->get(__PROPERTY__));
        set (bool|string $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $live_broadcast_text {
        get => Types::string($this->get(__PROPERTY__), 'Live Broadcast', true);
        set (string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $enable_auto_cue {
        get => Types::bool($this->get(__PROPERTY__));
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $write_playlists_to_liquidsoap {
        get => Types::bool($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    /*
     * Liquidsoap Custom Configuration Sections
     */

    public const string CUSTOM_TOP = 'custom_config_top';

    public ?string $custom_config_top {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public const string CUSTOM_PRE_PLAYLISTS = 'custom_config_pre_playlists';

    public ?string $custom_config_pre_playlists {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public const string CUSTOM_PRE_LIVE = 'custom_config_pre_live';

    public ?string $custom_config_pre_live {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public const string CUSTOM_PRE_FADE = 'custom_config_pre_fade';

    public ?string $custom_config_pre_fade {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public const string CUSTOM_PRE_BROADCAST = 'custom_config';

    /**
     * Now used as pre-broadcast custom config
     */
    public ?string $custom_config {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public const string CUSTOM_BOTTOM = 'custom_config_bottom';

    public ?string $custom_config_bottom {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

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
