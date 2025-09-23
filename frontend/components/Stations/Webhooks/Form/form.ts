import {WebhookTypes} from "~/entities/ApiInterfaces.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {ref} from "vue";
import {merge} from "es-toolkit/compat";
import {defineStore} from "pinia";
import {WebhookHooks, WebhookRecord, WebhookRecordCommon, WebhookRecordCommonMessages} from "~/entities/Webhooks.ts";

export const useStationsWebhooksForm = defineStore(
    'form-stations-webhooks',
    () => {
        const {$gettext} = useTranslate();

        const type = ref<WebhookTypes | null>(null);

        const getBlankForm = (formType: WebhookTypes | null): WebhookRecord => {
            const commonConfig: WebhookRecordCommon = {
                name: '',
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

            switch (formType) {
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

        };

        const form = ref<WebhookRecord>(getBlankForm(null));

        const setType = (newType: WebhookTypes | null): void => {
            type.value = newType;
            form.value = getBlankForm(newType);
        }

        const $reset = () => {
            setType(null);
        }

        return {
            type,
            setType,
            form,
            $reset,
            getBlankForm
        }
    }
);
