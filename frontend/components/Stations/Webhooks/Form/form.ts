import {useResettableRef} from "~/functions/useResettableRef.ts";
import {WebhookTypes} from "~/entities/ApiInterfaces.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {ref} from "vue";
import {merge} from "lodash";

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

type WebhookHooks =
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

export const useStationWebhooksForm = () => {
    const {$gettext} = useTranslate();

    const type = ref<WebhookTypes | null>(null);

    const {record: form, reset: resetForm} = useResettableRef<WebhookRecord>(() => {
        const commonConfig: WebhookRecordCommon = {
            name: null,
            triggers: [],
            config: {
                rate_limit: 0,
            }
        };

        const defaultMessages: WebhookRecordCommonMessages = {
            message: $gettext(
                'Now playing on %{station}: %{title} by %{artist}! Tune in now: %{url}',
                {
                    station: '{{ station.name }}',
                    title: '{{ now_playing.song.title }}',
                    artist: '{{ now_playing.song.artist }}',
                    url: '{{ station.public_player_url }}'
                }
            ),
            message_song_changed_live: $gettext(
                'Now playing on %{station}: %{title} by %{artist} with your host, %{dj}! Tune in now: %{url}',
                {
                    station: '{{ station.name }}',
                    title: '{{ now_playing.song.title }}',
                    artist: '{{ now_playing.song.artist }}',
                    dj: '{{ live.streamer_name }}',
                    url: '{{ station.public_player_url }}'
                }
            ),
            message_live_connect: $gettext(
                '%{dj} is now live on %{station}! Tune in now: %{url}',
                {
                    dj: '{{ live.streamer_name }}',
                    station: '{{ station.name }}',
                    url: '{{ station.public_player_url }}'
                }
            ),
            message_live_disconnect: $gettext(
                'Thanks for listening to %{station}!',
                {
                    station: '{{ station.name }}',
                }
            ),
            message_station_offline: $gettext(
                '%{station} is going offline for now.',
                {
                    station: '{{ station.name }}'
                }
            ),
            message_station_online: $gettext(
                '%{station} is back online! Tune in now: %{url}',
                {
                    station: '{{ station.name }}',
                    url: '{{ station.public_player_url }}'
                }
            )
        };

        let config: WebhookHooks = {
            type: null
        };

        switch (type.value) {
            case WebhookTypes.Generic:
                config = {
                    type: WebhookTypes.Generic,
                    config: {
                        webhook_url: '',
                        basic_auth_username: '',
                        basic_auth_password: '',
                        timeout: 5,
                    }
                };
                break;

            case WebhookTypes.Bluesky:
                config = {
                    type: WebhookTypes.Bluesky,
                    config: {
                        handle: '',
                        app_password: '',
                        ...defaultMessages
                    }
                };
                break;

            case WebhookTypes.Discord:
                config = {
                    type: WebhookTypes.Discord,
                    config: {
                        webhook_url: '',
                        content: $gettext(
                            'Now playing on %{station}:',
                            {'station': '{{ station.name }}'}
                        ),
                        title: '{{ now_playing.song.title }}',
                        description: '{{ now_playing.song.artist }}',
                        url: '{{ station.listen_url }}',
                        author: '{{ live.streamer_name }}',
                        thumbnail: '{{ now_playing.song.art }}',
                        footer: $gettext('Powered by AzuraCast'),
                        color: '#2196F3',
                        include_timestamp: true
                    }
                };
                break;

            case WebhookTypes.Email:
                config = {
                    type: WebhookTypes.Email,
                    config: {
                        to: '',
                        subject: '',
                        message: ''
                    }
                };
                break;

            case WebhookTypes.GetMeRadio:
                config = {
                    type: WebhookTypes.GetMeRadio,
                    config: {
                        token: '',
                        station_id: '',
                    }
                };
                break;

            case WebhookTypes.GoogleAnalyticsV4:
                config = {
                    type: WebhookTypes.GoogleAnalyticsV4,
                    config: {
                        api_secret: '',
                        measurement_id: ''
                    }
                };
                break;

            case WebhookTypes.GroupMe:
                config = {
                    type: WebhookTypes.GroupMe,
                    config: {
                        bot_id: '',
                        api: '',
                        text: $gettext(
                            'Now playing on %{station}: %{title} by %{artist}! Tune in now.',
                            {
                                station: '{{ station.name }}',
                                title: '{{ now_playing.song.title }}',
                                artist: '{{ now_playing.song.artist }}'
                            }
                        )
                    }
                };
                break;

            case WebhookTypes.Mastodon:
                config = {
                    type: WebhookTypes.Mastodon,
                    config: {
                        instance_url: '',
                        access_token: '',
                        visibility: 'public',
                        ...defaultMessages
                    }
                };
                break;

            case WebhookTypes.MatomoAnalytics:
                config = {
                    type: WebhookTypes.MatomoAnalytics,
                    config: {
                        matomo_url: '',
                        site_id: '',
                        token: ''
                    }
                };
                break;

            case WebhookTypes.RadioDe:
                config = {
                    type: WebhookTypes.RadioDe,
                    config: {
                        broadcastsubdomain: '',
                        apikey: ''
                    }
                };
                break;

            case WebhookTypes.RadioReg:
                config = {
                    type: WebhookTypes.RadioReg,
                    config: {
                        webhookurl: '',
                        apikey: ''
                    }
                };
                break;

            case WebhookTypes.Telegram:
                config = {
                    type: WebhookTypes.Telegram,
                    config: {
                        bot_token: '',
                        chat_id: '',
                        api: '',
                        text: $gettext(
                            'Now playing on %{station}: %{title} by %{artist}! Tune in now.',
                            {
                                station: '{{ station.name }}',
                                title: '{{ now_playing.song.title }}',
                                artist: '{{ now_playing.song.artist }}'
                            }
                        ),
                        parse_mode: 'Markdown'
                    }
                };
                break;

            case WebhookTypes.TuneIn:
                config = {
                    type: WebhookTypes.TuneIn,
                    config: {
                        station_id: '',
                        partner_id: '',
                        partner_key: ''
                    }
                };
                break;
        }

        return merge(commonConfig, config);
    });

    const setType = (newType: WebhookTypes): void => {
        type.value = newType;
        resetForm();
    }

    return {
        type,
        setType,
        form,
        resetForm
    }
};
