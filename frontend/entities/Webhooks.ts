import {useTranslate} from "~/vendor/gettext";
import {WebhookTriggers, WebhookTypes} from "~/entities/ApiInterfaces.ts";

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

export type WebhookTriggerDetails = { [key in WebhookTriggers]: WebhookTriggerDetail }

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

export interface WebhookTypeDetail {
    title: string,
    description: string,
}

export type ActiveWebhookTypes = Exclude<
    WebhookTypes,
    WebhookTypes.Twitter | WebhookTypes.GoogleAnalyticsV3
>;

export type WebhookTypeDetails = { [key in ActiveWebhookTypes]: WebhookTypeDetail }

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

export function getTriggers(type: WebhookTypes): WebhookTriggers[] {
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

export type WebhookRecordCommon = {
    name: string,
    triggers: string[],
    config: {
        rate_limit: number,
    }
};

export type WebhookRecordCommonMessages = {
    message: string,
    message_song_changed_live: string,
    message_live_connect: string,
    message_live_disconnect: string,
    message_station_offline: string,
    message_station_online: string,
}

export type WebhookRecordGeneric = {
    type: WebhookTypes.Generic,
    config: {
        webhook_url: string,
        basic_auth_username: string,
        basic_auth_password: string,
        timeout: number,
    }
}

export type WebhookRecordBluesky = {
    type: WebhookTypes.Bluesky,
    config: {
        handle: string,
        app_password: string
    } & WebhookRecordCommonMessages
}

export type WebhookRecordDiscord = {
    type: WebhookTypes.Discord,
    config: {
        webhook_url: string,
        content: string,
        title: string,
        description: string,
        url: string,
        author: string,
        thumbnail: string,
        footer: string,
        color: string,
        include_timestamp: boolean
    }
}

export type WebhookRecordEmail = {
    type: WebhookTypes.Email,
    config: {
        to: string,
        subject: string,
        message: string
    }
}

export type WebhookRecordGetMeRadio = {
    type: WebhookTypes.GetMeRadio,
    config: {
        token: string,
        station_id: string,
    }
}

export type WebhookRecordGoogleAnalyticsV4 = {
    type: WebhookTypes.GoogleAnalyticsV4,
    config: {
        api_secret: string,
        measurement_id: string
    }
}

export type WebhookRecordGroupMe = {
    type: WebhookTypes.GroupMe,
    config: {
        bot_id: string,
        api: string,
        text: string
    }
}

export type WebhookRecordMastodon = {
    type: WebhookTypes.Mastodon,
    config: {
        instance_url: string,
        access_token: string,
        visibility: string
    } & WebhookRecordCommonMessages
}

export type WebhookRecordMatomoAnalytics = {
    type: WebhookTypes.MatomoAnalytics,
    config: {
        matomo_url: string,
        site_id: string,
        token: string
    }
}

export type WebhookRecordRadioDe = {
    type: WebhookTypes.RadioDe,
    config: {
        broadcastsubdomain: string,
        apikey: string
    }
}

export type WebhookRecordRadioReg = {
    type: WebhookTypes.RadioReg,
    config: {
        webhookurl: string,
        apikey: string
    }
}

export type WebhookRecordTelegram = {
    type: WebhookTypes.Telegram,
    config: {
        bot_token: string,
        chat_id: string,
        api: string,
        text: string,
        parse_mode: string
    }
}

export type WebhookRecordTuneIn = {
    type: WebhookTypes.TuneIn,
    config: {
        station_id: string,
        partner_id: string,
        partner_key: string
    }
}

export type WebhookHooks =
    | WebhookRecordGeneric
    | WebhookRecordBluesky
    | WebhookRecordDiscord
    | WebhookRecordEmail
    | WebhookRecordGetMeRadio
    | WebhookRecordGoogleAnalyticsV4
    | WebhookRecordGroupMe
    | WebhookRecordMastodon
    | WebhookRecordMatomoAnalytics
    | WebhookRecordRadioDe
    | WebhookRecordRadioReg
    | WebhookRecordTelegram
    | WebhookRecordTuneIn
    | { type: null };

export type WebhookRecord = WebhookRecordCommon & WebhookHooks;

export type WebhookResponseBody = WebhookRecord & {
    type: WebhookTypes | null
}
