<?php
/**
 * Webhook Configuration
 */

use App\Entity\StationWebhook;
use App\Webhook\Connector;

return [
    'webhooks' => [
        Connector\Generic::NAME => [
            'class' => Connector\Generic::class,
            'name' => __('Generic Web Hook'),
            'description' => __('Automatically send a message to any URL when your station data changes.'),
        ],
        Connector\Email::NAME => [
            'class' => Connector\Email::class,
            'name' => __('Send E-mail'),
            'description' => __('Send an e-mail to specified address(es).'),
        ],
        Connector\TuneIn::NAME => [
            'class' => Connector\TuneIn::class,
            'name' => __('TuneIn AIR'),
            'description' => __('Send song metadata changes to TuneIn.'),
        ],
        Connector\Discord::NAME => [
            'class' => Connector\Discord::class,
            'name' => __('Discord Webhook'),
            'description' => __('Automatically send a customized message to your Discord server.'),
        ],
        Connector\Telegram::NAME => [
            'class' => Connector\Telegram::class,
            'name' => __('Telegram Chat Message'),
            'description' => __('Use the Telegram Bot API to send a message to a channel.'),
        ],
        Connector\Twitter::NAME => [
            'class' => Connector\Twitter::class,
            'name' => __('Twitter Post'),
            'description' => __('Automatically send a tweet.'),
        ],
        Connector\GoogleAnalytics::NAME => [
            'class' => Connector\GoogleAnalytics::class,
            'name' => __('Google Analytics Integration'),
            'description' => __('Send stream listener details to Google Analytics.'),
        ],
    ],

    // The triggers that can be selected for a web hook to trigger.
    'triggers' => [
        StationWebhook::TRIGGER_SONG_CHANGED => __('Any time the currently playing song changes'),
        StationWebhook::TRIGGER_LISTENER_GAINED => __('Any time the listener count increases'),
        StationWebhook::TRIGGER_LISTENER_LOST => __('Any time the listener count decreases'),
        StationWebhook::TRIGGER_LIVE_CONNECT => __('Any time a live streamer/DJ connects to the stream'),
        StationWebhook::TRIGGER_LIVE_DISCONNECT => __('Any time a live streamer/DJ disconnects from the stream'),
        StationWebhook::TRIGGER_STATION_OFFLINE => __('When the station broadcast goes offline.'),
        StationWebhook::TRIGGER_STATION_ONLINE => __('When the station broadcast comes online.'),
    ],
];
