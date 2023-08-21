<?php

declare(strict_types=1);

namespace App\Webhook\Enums;

use App\Webhook\Connector\Discord;
use App\Webhook\Connector\Email;
use App\Webhook\Connector\Generic;
use App\Webhook\Connector\GoogleAnalyticsV3;
use App\Webhook\Connector\GoogleAnalyticsV4;
use App\Webhook\Connector\Mastodon;
use App\Webhook\Connector\MatomoAnalytics;
use App\Webhook\Connector\RadioDe;
use App\Webhook\Connector\Telegram;
use App\Webhook\Connector\TuneIn;
use App\Webhook\Connector\Twitter;

enum WebhookTypes: string
{
    case Generic = 'generic';
    case Email = 'email';

    case TuneIn = 'tunein';
    case RadioDe = 'radiode';

    case Discord = 'discord';
    case Telegram = 'telegram';
    case Twitter = 'twitter';
    case Mastodon = 'mastodon';

    case GoogleAnalyticsV3 = 'google_analytics';
    case GoogleAnalyticsV4 = 'google_analytics_v4';
    case MatomoAnalytics = 'matomo_analytics';

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return match ($this) {
            self::Generic => Generic::class,
            self::Email => Email::class,
            self::TuneIn => TuneIn::class,
            self::RadioDe => RadioDe::class,
            self::Discord => Discord::class,
            self::Telegram => Telegram::class,
            self::Twitter => Twitter::class,
            self::Mastodon => Mastodon::class,
            self::GoogleAnalyticsV3 => GoogleAnalyticsV3::class,
            self::GoogleAnalyticsV4 => GoogleAnalyticsV4::class,
            self::MatomoAnalytics => MatomoAnalytics::class
        };
    }
}
