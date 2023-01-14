<?php

/**
 * Webhook Configuration
 */

use App\Entity\Enums\WebhookTriggers;
use App\Webhook\Connector;

$allTriggers = [
    WebhookTriggers::SongChanged->value,
    WebhookTriggers::SongChangedLive->value,
    WebhookTriggers::ListenerGained->value,
    WebhookTriggers::ListenerLost->value,
    WebhookTriggers::LiveConnect->value,
    WebhookTriggers::LiveDisconnect->value,
    WebhookTriggers::StationOffline->value,
    WebhookTriggers::StationOnline->value,
];

$allTriggersExceptListeners = array_diff(
    $allTriggers,
    [
        WebhookTriggers::ListenerGained->value,
        WebhookTriggers::ListenerLost->value,
    ]
);

return [
    'webhooks' => [
        Connector\Generic::NAME => [
            'class' => Connector\Generic::class,
            'name' => __('Generic Web Hook'),
            'description' => __('Automatically send a message to any URL when your station data changes.'),
            'triggers' => $allTriggers,
        ],
        Connector\Email::NAME => [
            'class' => Connector\Email::class,
            'name' => __('Send E-mail'),
            'description' => __('Send an e-mail to specified address(es).'),
            'triggers' => $allTriggers,
        ],
        Connector\TuneIn::NAME => [
            'class' => Connector\TuneIn::class,
            'name' => __('TuneIn AIR'),
            'description' => __('Send song metadata changes to TuneIn.'),
            'triggers' => [],
        ],
        Connector\Discord::NAME => [
            'class' => Connector\Discord::class,
            'name' => __('Discord Webhook'),
            'description' => __('Automatically send a customized message to your Discord server.'),
            'triggers' => $allTriggersExceptListeners,
        ],
        Connector\Telegram::NAME => [
            'class' => Connector\Telegram::class,
            'name' => __('Telegram Chat Message'),
            'description' => __('Use the Telegram Bot API to send a message to a channel.'),
            'triggers' => $allTriggersExceptListeners,
        ],
        Connector\Twitter::NAME => [
            'class' => Connector\Twitter::class,
            'name' => __('Twitter Post'),
            'description' => __('Automatically send a tweet.'),
            'triggers' => $allTriggersExceptListeners,
        ],
        Connector\Mastodon::NAME => [
            'class' => Connector\Mastodon::class,
            'name' => __('Mastodon Post'),
            'description' => __('Automatically publish to a Mastodon instance.'),
            'triggers' => $allTriggersExceptListeners,
        ],
        Connector\GoogleAnalyticsV3::NAME => [
            'class' => Connector\GoogleAnalyticsV3::class,
            'name' => __('Google Analytics V3 Integration'),
            'description' => __('Send stream listener details to Google Analytics.'),
            'triggers' => [],
        ],
        Connector\GoogleAnalyticsV4::NAME => [
            'class' => Connector\GoogleAnalyticsV4::class,
            'name' => __('Google Analytics V4 Integration'),
            'description' => __('Send stream listener details to Google Analytics.'),
            'triggers' => [],
        ],
        Connector\MatomoAnalytics::NAME => [
            'class' => Connector\MatomoAnalytics::class,
            'name' => __('Matomo Analytics Integration'),
            'description' => __('Send stream listener details to Matomo Analytics.'),
            'triggers' => [],
        ],
    ],
];
