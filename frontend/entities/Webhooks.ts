import {useTranslate} from "~/vendor/gettext";

export const WebhookTriggers = Object.freeze({
    SongChanged: 'song_changed',
    SongChangedLive: 'song_changed_live',
    ListenerGained: 'listener_gained',
    ListenerLost: 'listener_lost',
    LiveConnect: 'live_connect',
    LiveDisconnect: 'live_disconnect',
    StationOffline: 'station_offline',
    StationOnline: 'station_online'
} as const);

export type WebhookTrigger = typeof WebhookTriggers[keyof typeof WebhookTriggers];

const allTriggers = [
    WebhookTriggers.SongChanged,
    WebhookTriggers.SongChangedLive,
    WebhookTriggers.ListenerGained,
    WebhookTriggers.ListenerLost,
    WebhookTriggers.LiveConnect,
    WebhookTriggers.LiveDisconnect,
    WebhookTriggers.StationOffline,
    WebhookTriggers.StationOnline
];

const allTriggersExceptListeners = [
    WebhookTriggers.SongChanged,
    WebhookTriggers.SongChangedLive,
    WebhookTriggers.LiveConnect,
    WebhookTriggers.LiveDisconnect,
    WebhookTriggers.StationOffline,
    WebhookTriggers.StationOnline
];

export interface WebhookTriggerDetail {
    title: string,
    description: string,
}

export type WebhookTriggerDetails = { [key in WebhookTrigger]: WebhookTriggerDetail }

export function useTriggerDetails(): WebhookTriggerDetails {
    const {$gettext} = useTranslate();

    return {
        [WebhookTriggers.SongChanged]: {
            title: $gettext('Song Change'),
            description: $gettext('Any time the currently playing song changes')
        },
        [WebhookTriggers.SongChangedLive]: {
            title: $gettext('Song Change (Live Only)'),
            description: $gettext('When the song changes and a live streamer/DJ is connected')
        },
        [WebhookTriggers.ListenerGained]: {
            title: $gettext('Listener Gained'),
            description: $gettext('Any time the listener count increases'),
        },
        [WebhookTriggers.ListenerLost]: {
            title: $gettext('Listener Lost'),
            description: $gettext('Any time the listener count decreases'),
        },
        [WebhookTriggers.LiveConnect]: {
            title: $gettext('Live Streamer/DJ Connected'),
            description: $gettext('Any time a live streamer/DJ connects to the stream'),
        },
        [WebhookTriggers.LiveDisconnect]: {
            title: $gettext('Live Streamer/DJ Disconnected'),
            description: $gettext('Any time a live streamer/DJ disconnects from the stream'),
        },
        [WebhookTriggers.StationOffline]: {
            title: $gettext('Station Goes Offline'),
            description: $gettext('When the station broadcast goes offline'),
        },
        [WebhookTriggers.StationOnline]: {
            title: $gettext('Station Goes Online'),
            description: $gettext('When the station broadcast comes online'),
        },
    };
}

export const WebhookTypes = Object.freeze({
    Generic: 'generic',
    Email: 'email',
    TuneIn: 'tunein',
    RadioDe: 'radiode',
    RadioReg: 'radioreg',
    GetMeRadio: 'getmeradio',
    Discord: 'discord',
    Telegram: 'telegram',
    GroupMe: 'groupme',
    Mastodon: 'mastodon',
    Bluesky: 'bluesky',
    GoogleAnalyticsV4: 'google_analytics_v4',
    MatomoAnalytics: 'matomo_analytics'
} as const);

export type WebhookType = typeof WebhookTypes[keyof typeof WebhookTypes];

export interface WebhookTypeDetail {
    title: string,
    description: string,
}

export type WebhookTypeDetails = { [key in WebhookType]: WebhookTypeDetail }

export function useTypeDetails(): WebhookTypeDetails {
    const {$gettext} = useTranslate();

    return {
        [WebhookTypes.Generic]: {
            title: $gettext('Generic Web Hook'),
            description: $gettext('Automatically send a message to any URL when your station data changes.')
        },
        [WebhookTypes.Email]: {
            title: $gettext('Send E-mail'),
            description: $gettext('Send an e-mail to specified address(es).')
        },
        [WebhookTypes.TuneIn]: {
            title: $gettext('TuneIn AIR'),
            description: $gettext('Send song metadata changes to %{service}.', {service: 'TuneIn'})
        },
        [WebhookTypes.RadioDe]: {
            title: $gettext('Radio.de'),
            description: $gettext('Send song metadata changes to %{service}.', {service: 'Radio.de'})
        },
        [WebhookTypes.RadioReg]: {
            title: $gettext('RadioReg.net'),
            description: $gettext('Send song metadata changes to %{service}.', {service: 'RadioReg'})
        },
        [WebhookTypes.GetMeRadio]: {
            title: $gettext('GetMeRadio'),
            description: $gettext('Send song metadata changes to %{service}', {service: 'GetMeRadio'})
        },
        [WebhookTypes.Discord]: {
            title: $gettext('Discord Webhook'),
            description: $gettext('Automatically send a customized message to your Discord server.')
        },
        [WebhookTypes.Telegram]: {
            title: $gettext('Telegram Chat Message'),
            description: $gettext('Use the Telegram Bot API to send a message to a channel.')
        },
        [WebhookTypes.GroupMe]: {
            title: $gettext('GroupMe Chat Message'),
            description: $gettext('Use the GroupMe Bot API to send a message to a channel.'),
        },
        [WebhookTypes.Mastodon]: {
            title: $gettext('Mastodon Post'),
            description: $gettext('Automatically publish to a Mastodon instance.')
        },
        [WebhookTypes.Bluesky]: {
            title: $gettext('Bluesky Post'),
            description: $gettext('Automatically publish to Bluesky.')
        },
        [WebhookTypes.GoogleAnalyticsV4]: {
            title: $gettext('Google Analytics V4 Integration'),
            description: $gettext('Send stream listener details to Google Analytics.')
        },
        [WebhookTypes.MatomoAnalytics]: {
            title: $gettext('Matomo Analytics Integration'),
            description: $gettext('Send stream listener details to Matomo Analytics.')
        },
    };
}

export function getTriggers(type: WebhookType): WebhookTrigger[] {
    switch (type) {
        case WebhookTypes.TuneIn:
        case WebhookTypes.RadioDe:
        case WebhookTypes.RadioReg:
        case WebhookTypes.GetMeRadio:
        case WebhookTypes.GoogleAnalyticsV4:
        case WebhookTypes.MatomoAnalytics:
            return [];

        case WebhookTypes.Generic:
        case WebhookTypes.Email:
            return allTriggers;

        case WebhookTypes.Discord:
        case WebhookTypes.Telegram:
        case WebhookTypes.GroupMe:
        case WebhookTypes.Mastodon:
        default:
            return allTriggersExceptListeners;
    }
}
