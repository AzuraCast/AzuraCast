import {useTranslate} from "~/vendor/gettext";

export enum WebhookTrigger {
    SongChanged = 'song_changed',
    SongChangedLive = 'song_changed_live',
    ListenerGained = 'listener_gained',
    ListenerLost = 'listener_lost',
    LiveConnect = 'live_connect',
    LiveDisconnect = 'live_disconnect',
    StationOffline = 'station_offline',
    StationOnline = 'station_online'
}

const allTriggers = [
    WebhookTrigger.SongChanged,
    WebhookTrigger.SongChangedLive,
    WebhookTrigger.ListenerGained,
    WebhookTrigger.ListenerLost,
    WebhookTrigger.LiveConnect,
    WebhookTrigger.LiveDisconnect,
    WebhookTrigger.StationOffline,
    WebhookTrigger.StationOnline
];

const allTriggersExceptListeners = [
    WebhookTrigger.SongChanged,
    WebhookTrigger.SongChangedLive,
    WebhookTrigger.LiveConnect,
    WebhookTrigger.LiveDisconnect,
    WebhookTrigger.StationOffline,
    WebhookTrigger.StationOnline
];

export function useTriggerDetails() {
    const {$gettext} = useTranslate();

    return {
        [WebhookTrigger.SongChanged]: {
            title: $gettext('Song Change'),
            description: $gettext('Any time the currently playing song changes')
        },
        [WebhookTrigger.SongChangedLive]: {
            title: $gettext('Song Change (Live Only)'),
            description: $gettext('When the song changes and a live streamer/DJ is connected')
        },
        [WebhookTrigger.ListenerGained]: {
            title: $gettext('Listener Gained'),
            description: $gettext('Any time the listener count increases'),
        },
        [WebhookTrigger.ListenerLost]: {
            title: $gettext('Listener Lost'),
            description: $gettext('Any time the listener count decreases'),
        },
        [WebhookTrigger.LiveConnect]: {
            title: $gettext('Live Streamer/DJ Connected'),
            description: $gettext('Any time a live streamer/DJ connects to the stream'),
        },
        [WebhookTrigger.LiveDisconnect]: {
            title: $gettext('Live Streamer/DJ Disconnected'),
            description: $gettext('Any time a live streamer/DJ disconnects from the stream'),
        },
        [WebhookTrigger.StationOffline]: {
            title: $gettext('Station Goes Offline'),
            description: $gettext('When the station broadcast goes offline'),
        },
        [WebhookTrigger.StationOnline]: {
            title: $gettext('Station Goes Online'),
            description: $gettext('When the station broadcast comes online'),
        },
    };
}

export enum WebhookType {
    Generic = 'generic',
    Email = 'email',
    TuneIn = 'tunein',
    RadioDe = 'radiode',
    Discord = 'discord',
    Telegram = 'telegram',
    Twitter = 'twitter',
    Mastodon = 'mastodon',
    GoogleAnalyticsV3 = 'google_analytics',
    GoogleAnalyticsV4 = 'google_analytics_v4',
    MatomoAnalytics = 'matomo_analytics'
}

export function useTypeDetails() {
    const {$gettext} = useTranslate();

    return {
        [WebhookType.Generic]: {
            title: $gettext('Generic Web Hook'),
            description: $gettext('Automatically send a message to any URL when your station data changes.')
        },
        [WebhookType.Email]: {
            title: $gettext('Send E-mail'),
            description: $gettext('Send an e-mail to specified address(es).')
        },
        [WebhookType.TuneIn]: {
            title: $gettext('TuneIn AIR'),
            description: $gettext('Send song metadata changes to TuneIn.')
        },
        [WebhookType.RadioDe]: {
            title: $gettext('Radio.de'),
            description: $gettext('Send song metadata changes to Radio.de.')
        },
        [WebhookType.Discord]: {
            title: $gettext('Discord Webhook'),
            description: $gettext('Automatically send a customized message to your Discord server.')
        },
        [WebhookType.Telegram]: {
            title: $gettext('Telegram Chat Message'),
            description: $gettext('Use the Telegram Bot API to send a message to a channel.')
        },
        [WebhookType.Twitter]: {
            title: $gettext('Twitter Post'),
            description: $gettext('Automatically send a tweet.')
        },
        [WebhookType.Mastodon]: {
            title: $gettext('Mastodon Post'),
            description: $gettext('Automatically publish to a Mastodon instance.')
        },
        [WebhookType.GoogleAnalyticsV3]: {
            title: $gettext('Google Analytics V3 Integration'),
            description: $gettext('Send stream listener details to Google Analytics.')
        },
        [WebhookType.GoogleAnalyticsV4]: {
            title: $gettext('Google Analytics V4 Integration'),
            description: $gettext('Send stream listener details to Google Analytics.')
        },
        [WebhookType.MatomoAnalytics]: {
            title: $gettext('Matomo Analytics Integration'),
            description: $gettext('Send stream listener details to Matomo Analytics.')
        },
    };
}

export function getTriggers(type: WebhookType) {
    switch(type) {
        case WebhookType.TuneIn:
        case WebhookType.RadioDe:
        case WebhookType.GoogleAnalyticsV3:
        case WebhookType.GoogleAnalyticsV4:
        case WebhookType.MatomoAnalytics:
            return [];

        case WebhookType.Generic:
        case WebhookType.Email:
            return allTriggers;

        case WebhookType.Discord:
        case WebhookType.Telegram:
        case WebhookType.Twitter:
        case WebhookType.Mastodon:
        default:
            return allTriggersExceptListeners;
    }
}
