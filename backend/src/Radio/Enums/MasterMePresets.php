<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum MasterMePresets: string
{
    case MusicGeneral = 'music_general';
    case SpeechGeneral = 'speech_general';
    case EbuR128 = 'ebu_r128';
    case ApplePodcasts = 'apple_podcasts';
    case YouTube = 'youtube';

    public static function default(): self
    {
        return self::MusicGeneral;
    }

    public function getOptions(): array
    {
        $defaults = [
            "bypass" => false,
            "target" => -16,
            "brickwall_bypass" => false,
            "brickwall_ceiling" => -1.0,
            "brickwall_release" => 75.0,
            "eq_bypass" => false,
            "eq_highpass_freq" => 5.0,
            "eq_side_bandwidth" => 1.0,
            "eq_side_freq" => 600.0,
            "eq_side_gain" => 1.0,
            "eq_tilt_gain" => 0.0,
            "gate_attack" => 0.0,
            "gate_bypass" => true,
            "gate_hold" => 50.0,
            "gate_release" => 430.5,
            "gate_threshold" => -90.0,
            "kneecomp_attack" => 20.0,
            "kneecomp_bypass" => false,
            "kneecomp_dry_wet" => 50,
            "kneecomp_ff_fb" => 50,
            "kneecomp_knee" => 6.0,
            "kneecomp_link" => 60,
            "kneecomp_makeup" => 0.0,
            "kneecomp_release" => 340.0,
            "kneecomp_strength" => 20,
            "kneecomp_tar_thresh" => -4.0,
            "leveler_brake_threshold" => -10.0,
            "leveler_bypass" => false,
            "leveler_max" => 10.0,
            "leveler_max__" => 10.0,
            "leveler_speed" => 20,
            "limiter_attack" => 3.0,
            "limiter_bypass" => false,
            "limiter_ff_fb" => 50,
            "limiter_knee" => 3.0,
            "limiter_makeup" => 0.0,
            "limiter_release" => 40.0,
            "limiter_strength" => 80,
            "limiter_tar_thresh" => 6.0,
            "mscomp_bypass" => false,
            "high_attack" => 8.0,
            "high_crossover" => 8000.0,
            "high_knee" => 12.0,
            "high_link" => 30,
            "high_release" => 30.0,
            "high_strength" => 30,
            "high_tar_thresh" => -12.0,
            "low_attack" => 15.0,
            "low_crossover" => 60.0,
            "low_knee" => 12.0,
            "low_link" => 70,
            "low_release" => 150.0,
            "low_strength" => 10,
            "low_tar_thresh" => -3.0,
            "makeup" => 1.0,
            "dc_blocker" => false,
            "input_gain" => 0.0,
            "mono" => false,
            "phase_l" => false,
            "phase_r" => false,
            "stereo_correct" => false,
        ];

        return match ($this) {
            self::MusicGeneral => [
                ...$defaults,
            ],
            self::SpeechGeneral => [
                ...$defaults,
                "eq_highpass_freq" => 20.0,
                "eq_side_gain" => 0.0,
                "gate_attack" => 1.0,
                "gate_release" => 500.0,
                "kneecomp_attack" => 5.0,
                "kneecomp_knee" => 9.0,
                "kneecomp_release" => 50.0,
                "kneecomp_strength" => 15,
                "kneecomp_tar_thresh" => -6.0,
                "leveler_brake_threshold" => -20.0,
                "leveler_max" => 30.0,
                "leveler_max__" => 30.0,
                "limiter_attack" => 1.0,
                "limiter_strength" => 80,
                "limiter_tar_thresh" => 3.0,
                "high_attack" => 0.0,
                "high_release" => 50.0,
                "high_strength" => 40,
                "high_tar_thresh" => -7.0,
                "low_attack" => 10.0,
                "low_release" => 80.0,
                "low_strength" => 20,
                "low_tar_thresh" => -5.0,
                "dc_blocker" => true,
            ],
            self::EbuR128 => [
                ...$defaults,
                "target" => -23,
                "eq_highpass_freq" => 20.0,
                "eq_side_gain" => 0.0,
                "gate_attack" => 1.0,
                "gate_release" => 500.0,
                "kneecomp_attack" => 5.0,
                "kneecomp_bypass" => true,
                "kneecomp_knee" => 9.0,
                "kneecomp_release" => 50.0,
                "kneecomp_strength" => 15,
                "kneecomp_tar_thresh" => -6.0,
                "leveler_brake_threshold" => -20.0,
                "leveler_max" => 30.0,
                "leveler_max__" => 30.0,
                "leveler_speed" => 40,
                "limiter_attack" => 1.0,
                "limiter_tar_thresh" => 3.0,
                "high_attack" => 0.0,
                "high_release" => 50.0,
                "high_strength" => 40,
                "high_tar_thresh" => -8.0,
                "low_attack" => 10.0,
                "low_release" => 80.0,
                "low_strength" => 20,
                "low_tar_thresh" => -6.0,
                "dc_blocker" => true,
            ],
            self::ApplePodcasts => [
                ...$defaults,
                "bypass" => false,
                "eq_highpass_freq" => 20.0,
                "eq_side_gain" => 0.0,
                "gate_attack" => 1.0,
                "gate_release" => 500.0,
                "kneecomp_attack" => 5.0,
                "kneecomp_bypass" => true,
                "kneecomp_knee" => 9.0,
                "kneecomp_release" => 50.0,
                "kneecomp_strength" => 15,
                "kneecomp_tar_thresh" => -6.0,
                "leveler_brake_threshold" => -20.0,
                "leveler_max" => 30.0,
                "leveler_max__" => 30.0,
                "leveler_speed" => 50,
                "limiter_attack" => 1.0,
                "limiter_tar_thresh" => 3.0,
                "high_attack" => 0.0,
                "high_release" => 50.0,
                "high_strength" => 40,
                "high_tar_thresh" => -8.0,
                "low_attack" => 10.0,
                "low_release" => 80.0,
                "low_strength" => 20,
                "low_tar_thresh" => -6.0,
                "dc_blocker" => true,
            ],
            self::YouTube => [
                ...$defaults,
                "target" => -14,
                "eq_highpass_freq" => 20.0,
                "eq_side_gain" => 0.0,
                "gate_attack" => 1.0,
                "gate_release" => 500.0,
                "kneecomp_attack" => 5.0,
                "kneecomp_bypass" => true,
                "kneecomp_knee" => 9.0,
                "kneecomp_release" => 50.0,
                "kneecomp_strength" => 15,
                "kneecomp_tar_thresh" => -6.0,
                "leveler_brake_threshold" => -20.0,
                "leveler_max" => 30.0,
                "leveler_max__" => 30.0,
                "leveler_speed" => 50,
                "limiter_attack" => 1.0,
                "limiter_tar_thresh" => 3.0,
                "high_attack" => 0.0,
                "high_strength" => 40,
                "high_tar_thresh" => -8.0,
                "low_attack" => 10.0,
                "low_release" => 80.0,
                "low_strength" => 20,
                "low_tar_thresh" => -6.0,
                "dc_blocker" => true,
            ]
        };
    }
}
