<?php

declare(strict_types=1);

namespace App\Webhook\Enums;

use App\Webhook\Connector\Bluesky;
use App\Webhook\Connector\Discord;
use App\Webhook\Connector\Email;
use App\Webhook\Connector\Generic;
use App\Webhook\Connector\GetMeRadio;
use App\Webhook\Connector\GoogleAnalyticsV4;
use App\Webhook\Connector\GroupMe;
use App\Webhook\Connector\Mastodon;
use App\Webhook\Connector\MatomoAnalytics;
use App\Webhook\Connector\RadioDe;
use App\Webhook\Connector\RadioReg;
use App\Webhook\Connector\Telegram;
use App\Webhook\Connector\TuneIn;
use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum WebhookTypes: string
{
    case Generic = 'generic';
    case Email = 'email';

    case TuneIn = 'tunein';
    case RadioDe = 'radiode';
    case RadioReg = 'radioreg';
    case GetMeRadio = 'getmeradio';

    case Discord = 'discord';
    case Telegram = 'telegram';
    case GroupMe = 'groupme';
    case Mastodon = 'mastodon';
    case Bluesky = 'bluesky';

    case GoogleAnalyticsV4 = 'google_analytics_v4';
    case MatomoAnalytics = 'matomo_analytics';

    // Retired connectors
    case Twitter = 'twitter';
    case GoogleAnalyticsV3 = 'google_analytics';

    /**
     * @return class-string|null
     */
    public function getClass(): ?string
    {
        return match ($this) {
            self::Generic => Generic::class,
            self::Email => Email::class,
            self::TuneIn => TuneIn::class,
            self::RadioReg => RadioReg::class,
            self::RadioDe => RadioDe::class,
            self::GetMeRadio => GetMeRadio::class,
            self::Discord => Discord::class,
            self::Telegram => Telegram::class,
            self::GroupMe => GroupMe::class,
            self::Mastodon => Mastodon::class,
            self::Bluesky => Bluesky::class,
            self::GoogleAnalyticsV4 => GoogleAnalyticsV4::class,
            self::MatomoAnalytics => MatomoAnalytics::class,
            default => null
        };
    }
}
