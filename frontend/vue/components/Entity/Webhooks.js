import {useTranslate} from "~/vendor/gettext";

export const WEBHOOK_TRIGGER_SONG_CHANGED = 'song_changed';
export const WEBHOOK_TRIGGER_SONG_CHANGED_LIVE = 'song_changed_live';
export const WEBHOOK_TRIGGER_LISTENER_GAINED = 'listener_gained';
export const WEBHOOK_TRIGGER_LISTENER_LOST = 'listener_lost';
export const WEBHOOK_TRIGGER_LIVE_CONNECT = 'live_connect';
export const WEBHOOK_TRIGGER_LIVE_DISCONNECT = 'live_disconnect';
export const WEBHOOK_TRIGGER_STATION_OFFLINE = 'station_offline';
export const WEBHOOK_TRIGGER_STATION_ONLINE = 'station_online';

const allTriggers = [
    WEBHOOK_TRIGGER_SONG_CHANGED,
    WEBHOOK_TRIGGER_SONG_CHANGED_LIVE,
    WEBHOOK_TRIGGER_LISTENER_GAINED,
    WEBHOOK_TRIGGER_LISTENER_LOST,
    WEBHOOK_TRIGGER_LIVE_CONNECT,
    WEBHOOK_TRIGGER_LIVE_DISCONNECT,
    WEBHOOK_TRIGGER_STATION_OFFLINE,
    WEBHOOK_TRIGGER_STATION_ONLINE
];

const allTriggersExceptListeners = [
    WEBHOOK_TRIGGER_SONG_CHANGED,
    WEBHOOK_TRIGGER_SONG_CHANGED_LIVE,
    WEBHOOK_TRIGGER_LIVE_CONNECT,
    WEBHOOK_TRIGGER_LIVE_DISCONNECT,
    WEBHOOK_TRIGGER_STATION_OFFLINE,
    WEBHOOK_TRIGGER_STATION_ONLINE
];

export function useTriggerDetails() {
    const {$gettext} = useTranslate();

    return {
        [WEBHOOK_TRIGGER_SONG_CHANGED]: {
            title: $gettext('Song Change'),
            description: $gettext('Any time the currently playing song changes')
        },
        [WEBHOOK_TRIGGER_SONG_CHANGED_LIVE]: {
            title: $gettext('Song Change (Live Only)'),
            description: $gettext('When the song changes and a live streamer/DJ is connected')
        },
        [WEBHOOK_TRIGGER_LISTENER_GAINED]: {
            title: $gettext('Listener Gained'),
            description: $gettext('Any time the listener count increases'),
        },
        [WEBHOOK_TRIGGER_LISTENER_LOST]: {
            title: $gettext('Listener Lost'),
            description: $gettext('Any time the listener count decreases'),
        },
        [WEBHOOK_TRIGGER_LIVE_CONNECT]: {
            title: $gettext('Live Streamer/DJ Connected'),
            description: $gettext('Any time a live streamer/DJ connects to the stream'),
        },
        [WEBHOOK_TRIGGER_LIVE_DISCONNECT]: {
            title: $gettext('Live Streamer/DJ Disconnected'),
            description: $gettext('Any time a live streamer/DJ disconnects from the stream'),
        },
        [WEBHOOK_TRIGGER_LIVE_DISCONNECT]: {
            title: $gettext('Live Streamer/DJ Disconnected'),
            description: $gettext('Any time a live streamer/DJ disconnects from the stream'),
        },
        [WEBHOOK_TRIGGER_STATION_OFFLINE]: {
            title: $gettext('Station Goes Offline'),
            description: $gettext('When the station broadcast goes offline'),
        },
        [WEBHOOK_TRIGGER_STATION_ONLINE]: {
            title: $gettext('Station Goes Online'),
            description: $gettext('When the station broadcast comes online'),
        },
    };
}

export const WEBHOOK_TYPE_GENERIC = 'generic';
export const WEBHOOK_TYPE_EMAIL = 'email';
export const WEBHOOK_TYPE_TUNEIN = 'tunein';
export const WEBHOOK_TYPE_DISCORD = 'discord';
export const WEBHOOK_TYPE_TELEGRAM = 'telegram';
export const WEBHOOK_TYPE_TWITTER = 'twitter';
export const WEBHOOK_TYPE_MASTODON = 'mastodon';
export const WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3 = 'google_analytics';
export const WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4 = 'google_analytics_v4';
export const WEBHOOK_TYPE_MATOMO_ANALYTICS = 'matomo_analytics';

export function useTypeDetails() {
    const {$gettext} = useTranslate();

    return {
        [WEBHOOK_TYPE_GENERIC]: {
            title: $gettext('Generic Web Hook'),
            description: $gettext('Automatically send a message to any URL when your station data changes.')
        },
        [WEBHOOK_TYPE_EMAIL]: {
            title: $gettext('Send E-mail'),
            description: $gettext('Send an e-mail to specified address(es).')
        },
        [WEBHOOK_TYPE_TUNEIN]: {
            title: $gettext('TuneIn AIR'),
            description: $gettext('Send song metadata changes to TuneIn.')
        },
        [WEBHOOK_TYPE_DISCORD]: {
            title: $gettext('Discord Webhook'),
            description: $gettext('Automatically send a customized message to your Discord server.')
        },
        [WEBHOOK_TYPE_TELEGRAM]: {
            title: $gettext('Telegram Chat Message'),
            description: $gettext('Use the Telegram Bot API to send a message to a channel.')
        },
        [WEBHOOK_TYPE_TWITTER]: {
            title: $gettext('Twitter Post'),
            description: $gettext('Automatically send a tweet.')
        },
        [WEBHOOK_TYPE_MASTODON]: {
            title: $gettext('Mastodon Post'),
            description: $gettext('Automatically publish to a Mastodon instance.')
        },
        [WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3]: {
            title: $gettext('Google Analytics V3 Integration'),
            description: $gettext('Send stream listener details to Google Analytics.')
        },
        [WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4]: {
            title: $gettext('Google Analytics V4 Integration'),
            description: $gettext('Send stream listener details to Google Analytics.')
        },
        [WEBHOOK_TYPE_MATOMO_ANALYTICS]: {
            title: $gettext('Matomo Analytics Integration'),
            description: $gettext('Send stream listener details to Matomo Analytics.')
        },
    };
}

export function getTriggers(type) {
    switch(type) {
        case WEBHOOK_TYPE_TUNEIN:
        case WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3:
        case WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4:
        case WEBHOOK_TYPE_MATOMO_ANALYTICS:
            return [];

        case WEBHOOK_TYPE_GENERIC:
        case WEBHOOK_TYPE_EMAIL:
            return allTriggers;

        case WEBHOOK_TYPE_DISCORD:
        case WEBHOOK_TYPE_TELEGRAM:
        case WEBHOOK_TYPE_TWITTER:
        case WEBHOOK_TYPE_MASTODON:
        default:
            return allTriggersExceptListeners;
    }
}
