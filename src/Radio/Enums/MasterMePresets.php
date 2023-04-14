<?php

declare(strict_types=1);

namespace App\Radio\Enums;

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
        return match($this) {
            self::MusicGeneral => [
                "globalbypass" => false,
                "masterme_easy_target" => -16,
                "masterme_expert_brickwall_brickwallbypass" => false,
                "masterme_expert_brickwall_brickwallceiling" => -1,
                "masterme_expert_brickwall_brickwallrelease" => 75,
                "masterme_expert_eq_eqbypass" => false,
                "masterme_expert_eq_highpass_eqhighpassfreq" => 5,
                "masterme_expert_eq_sideeq_eqsidebandwidth" => 1,
                "masterme_expert_eq_sideeq_eqsidefreq" => 600,
                "masterme_expert_eq_sideeq_eqsidegain" => 1,
                "masterme_expert_eq_tilteq_eqtiltgain" => 0,
                "masterme_expert_gate_gateattack" => 0,
                "masterme_expert_gate_gatebypass" => true,
                "masterme_expert_gate_gatehold" => 50,
                "masterme_expert_gate_gaterelease" => 430.5,
                "masterme_expert_gate_gatethreshold" => -90,
                "masterme_expert_kneecomp_kneecompattack" => 20,
                "masterme_expert_kneecomp_kneecompbypass" => false,
                "masterme_expert_kneecomp_kneecompdrywet" => 50,
                "masterme_expert_kneecomp_kneecompff_fb" => 50,
                "masterme_expert_kneecomp_kneecompknee" => 6,
                "masterme_expert_kneecomp_kneecomplink" => 60,
                "masterme_expert_kneecomp_kneecompmakeup" => 0,
                "masterme_expert_kneecomp_kneecomprelease" => 340,
                "masterme_expert_kneecomp_kneecompstrength" => 20,
                "masterme_expert_kneecomp_kneecomptar_thresh" => -4,
                "masterme_expert_leveler_levelerbrakethreshold" => -10,
                "masterme_expert_leveler_levelerbypass" => false,
                "masterme_expert_leveler_levelermax" => 10,
                "masterme_expert_leveler_levelerspeed" => 20,
                "masterme_expert_limiter_limiterattack" => 3,
                "masterme_expert_limiter_limiterbypass" => false,
                "masterme_expert_limiter_limiterff_fb" => 50,
                "masterme_expert_limiter_limiterknee" => 3,
                "masterme_expert_limiter_limitermakeup" => 0,
                "masterme_expert_limiter_limiterrelease" => 40,
                "masterme_expert_limiter_limiterstrength" => 80,
                "masterme_expert_limiter_limitertar_thresh" => 6,
                "masterme_expert_mscomp_bypass_mscompbypass" => false,
                "masterme_expert_mscomp_highband_highattack" => 8,
                "masterme_expert_mscomp_highband_highcrossover" => 8000,
                "masterme_expert_mscomp_highband_highknee" => 12,
                "masterme_expert_mscomp_highband_highlink" => 30,
                "masterme_expert_mscomp_highband_highrelease" => 30,
                "masterme_expert_mscomp_highband_highstrength" => 30,
                "masterme_expert_mscomp_highband_hightar_thresh" => -12,
                "masterme_expert_mscomp_lowband_lowattack" => 15,
                "masterme_expert_mscomp_lowband_lowcrossover" => 60,
                "masterme_expert_mscomp_lowband_lowknee" => 12,
                "masterme_expert_mscomp_lowband_lowlink" => 70,
                "masterme_expert_mscomp_lowband_lowrelease" => 150,
                "masterme_expert_mscomp_lowband_lowstrength" => 10,
                "masterme_expert_mscomp_lowband_lowtar_thresh" => -3,
                "masterme_expert_mscomp_out_makeup" => 1,
                "masterme_expert_pre_processing_dcblocker" => false,
                "masterme_expert_pre_processing_inputgain" => 0,
                "masterme_expert_pre_processing_mono" => false,
                "masterme_expert_pre_processing_phasel" => false,
                "masterme_expert_pre_processing_phaser" => false,
                "masterme_expert_pre_processing_stereocorrect" => false,
            ],
            self::SpeechGeneral => [
                "globalbypass" => false,
                "masterme_easy_target" => -16,
                "masterme_expert_brickwall_brickwallbypass" => false,
                "masterme_expert_brickwall_brickwallceiling" => -1,
                "masterme_expert_brickwall_brickwallrelease" => 75,
                "masterme_expert_eq_eqbypass" => false,
                "masterme_expert_eq_highpass_eqhighpassfreq" => 5,
                "masterme_expert_eq_sideeq_eqsidebandwidth" => 1,
                "masterme_expert_eq_sideeq_eqsidefreq" => 600,
                "masterme_expert_eq_sideeq_eqsidegain" => 1,
                "masterme_expert_eq_tilteq_eqtiltgain" => 0,
                "masterme_expert_gate_gateattack" => 0,
                "masterme_expert_gate_gatebypass" => true,
                "masterme_expert_gate_gatehold" => 50,
                "masterme_expert_gate_gaterelease" => 430.5,
                "masterme_expert_gate_gatethreshold" => -90,
                "masterme_expert_kneecomp_kneecompattack" => 20,
                "masterme_expert_kneecomp_kneecompbypass" => false,
                "masterme_expert_kneecomp_kneecompdrywet" => 50,
                "masterme_expert_kneecomp_kneecompff_fb" => 50,
                "masterme_expert_kneecomp_kneecompknee" => 6,
                "masterme_expert_kneecomp_kneecomplink" => 60,
                "masterme_expert_kneecomp_kneecompmakeup" => 0,
                "masterme_expert_kneecomp_kneecomprelease" => 340,
                "masterme_expert_kneecomp_kneecompstrength" => 20,
                "masterme_expert_kneecomp_kneecomptar_thresh" => -4,
                "masterme_expert_leveler_levelerbrakethreshold" => -10,
                "masterme_expert_leveler_levelerbypass" => false,
                "masterme_expert_leveler_levelermax" => 10,
                "masterme_expert_leveler_levelerspeed" => 20,
                "masterme_expert_limiter_limiterattack" => 3,
                "masterme_expert_limiter_limiterbypass" => false,
                "masterme_expert_limiter_limiterff_fb" => 50,
                "masterme_expert_limiter_limiterknee" => 3,
                "masterme_expert_limiter_limitermakeup" => 0,
                "masterme_expert_limiter_limiterrelease" => 40,
                "masterme_expert_limiter_limiterstrength" => 80,
                "masterme_expert_limiter_limitertar_thresh" => 6,
                "masterme_expert_mscomp_bypass_mscompbypass" => false,
                "masterme_expert_mscomp_highband_highattack" => 8,
                "masterme_expert_mscomp_highband_highcrossover" => 8000,
                "masterme_expert_mscomp_highband_highknee" => 12,
                "masterme_expert_mscomp_highband_highlink" => 30,
                "masterme_expert_mscomp_highband_highrelease" => 30,
                "masterme_expert_mscomp_highband_highstrength" => 30,
                "masterme_expert_mscomp_highband_hightar_thresh" => -12,
                "masterme_expert_mscomp_lowband_lowattack" => 15,
                "masterme_expert_mscomp_lowband_lowcrossover" => 60,
                "masterme_expert_mscomp_lowband_lowknee" => 12,
                "masterme_expert_mscomp_lowband_lowlink" => 70,
                "masterme_expert_mscomp_lowband_lowrelease" => 150,
                "masterme_expert_mscomp_lowband_lowstrength" => 10,
                "masterme_expert_mscomp_lowband_lowtar_thresh" => -3,
                "masterme_expert_mscomp_out_makeup" => 1,
                "masterme_expert_pre_processing_dcblocker" => false,
                "masterme_expert_pre_processing_inputgain" => 0,
                "masterme_expert_pre_processing_mono" => false,
                "masterme_expert_pre_processing_phasel" => false,
                "masterme_expert_pre_processing_phaser" => false,
                "masterme_expert_pre_processing_stereocorrect" => false,
            ],
            self::EbuR128 => [

            ],
            self::ApplePodcasts => [

            ],
            self::YouTube => [

            ]
        };
    }
}
