import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {createVariant} from "@regle/core";
import {literal, required, withMessage} from "@regle/rules";
import {WebhookTypes} from "~/entities/ApiInterfaces.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {ref} from "vue";

export const useStationsWebhooksForm = defineStore(
    'form-stations-webhooks',
    () => {
        const {$gettext} = useTranslate();

        const defaultMessages = {
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

        const hexColor = withMessage(
            (value: string) => value === '' || /^#?[0-9A-F]{6}$/i.test(value),
            $gettext('This field must be a valid, non-transparent 6-character hex color.')
        );

        const type = ref<WebhookTypes | null>(null);

        const {record: form, reset} = useResettableRef(() => {
            let customConfig: Record<string, any> = {};

            switch (type.value) {
                case WebhookTypes.Generic:
                    customConfig = {
                        webhook_url: '',
                        basic_auth_username: '',
                        basic_auth_password: '',
                        timeout: '5',
                    };
                    break;

                case WebhookTypes.Bluesky:
                    customConfig = {
                        handle: '',
                        app_password: '',
                        ...defaultMessages
                    };
                    break;

                case WebhookTypes.Discord:
                    customConfig = {
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
                    };
                    break;

                case WebhookTypes.Email:
                    customConfig = {
                        to: '',
                        subject: '',
                        message: ''
                    };
                    break;

                case WebhookTypes.GetMeRadio:
                    customConfig = {
                        token: '',
                        station_id: '',
                    };
                    break;

                case WebhookTypes.GoogleAnalyticsV4:
                    customConfig = {
                        api_secret: '',
                        measurement_id: ''
                    };
                    break;

                case WebhookTypes.GroupMe:
                    customConfig = {
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
                    };
                    break;

                case WebhookTypes.Mastodon:
                    customConfig = {
                        instance_url: '',
                        access_token: '',
                        visibility: 'public',
                        ...defaultMessages
                    };
                    break;

                case WebhookTypes.MatomoAnalytics:
                    customConfig = {
                        matomo_url: '',
                        site_id: '',
                        token: ''
                    };
                    break;

                case WebhookTypes.RadioDe:
                    customConfig = {
                        broadcastsubdomain: '',
                        apikey: ''
                    };
                    break;

                case WebhookTypes.RadioReg:
                    customConfig = {
                        webhookurl: '',
                        apikey: ''
                    };
                    break;

                case WebhookTypes.Telegram:
                    customConfig = {
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
                    };
                    break;

                case WebhookTypes.TuneIn:
                    customConfig = {
                        station_id: '',
                        partner_id: '',
                        partner_key: ''
                    };
                    break;
            }

            return {
                type: null,
                name: null,
                triggers: [],
                config: {
                    rate_limit: 0,
                    ...customConfig
                }
            };
        });

        const {r$} = useAppRegle(
            form,
            () => {
                const variant = createVariant(form, 'type', [
                    {
                        type: {literal: literal(WebhookTypes.Generic)},
                        config: {
                            webhook_url: {required},
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.Bluesky)},
                        config: {
                            handle: {required},
                            app_password: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.Discord)},
                        config: {
                            webhook_url: {required},
                            color: {hexColor},
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.Email)},
                        config: {
                            to: {required},
                            subject: {required},
                            message: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.GetMeRadio)},
                        config: {
                            token: {required},
                            station_id: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.GoogleAnalyticsV4)},
                        config: {
                            api_secret: {required},
                            measurement_id: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.GroupMe)},
                        config: {
                            bot_id: {required},
                            text: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.Mastodon)},
                        config: {
                            instance_url: {required},
                            access_token: {required},
                            visibility: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.MatomoAnalytics)},
                        config: {
                            matomo_url: {required},
                            site_id: {required},
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.RadioDe)},
                        config: {
                            broadcastsubdomain: {required},
                            apikey: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.RadioReg)},
                        config: {
                            webhookurl: {required},
                            apikey: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.Telegram)},
                        config: {
                            bot_token: {required},
                            chat_id: {required},
                            text: {required},
                            parse_mode: {required}
                        }
                    },
                    {
                        type: {literal: literal(WebhookTypes.TuneIn)},
                        config: {
                            station_id: {required},
                            partner_id: {required},
                            partner_key: {required},
                        }
                    },
                    {
                        type: {required}
                    }
                ]);

                return {
                    name: {required},
                    ...variant.value
                };
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.name,
                        fields.triggers,
                        fields.config.rate_limit
                    ],
                    genericWebhookTab: [
                        fields.config.webhook_url,
                        fields.config.basic_auth_username,
                        fields.config.basic_auth_password,
                        fields.config.timeout
                    ],
                    blueskyWebhookTab: [
                        fields.config.handle,
                        fields.config.app_password
                    ],
                    discordWebhookTab: [
                        fields.config.webhook_url,
                        fields.config.content,
                        fields.config.title,
                        fields.config.description,
                        fields.config.url,
                        fields.config.author,
                        fields.config.thumbnail,
                        fields.config.footer,
                        fields.config.color,
                        fields.config.include_timestamp
                    ],
                    emailWebhookTab: [
                        fields.config.to,
                        fields.config.subject,
                        fields.config.message
                    ],
                    getMeRadioWebhookTab: [
                        fields.config.token,
                        fields.config.station_id,
                    ],
                    googleAnalyticsV4WebhookTab: [
                        fields.config.api_secret,
                        fields.config.measurement_id
                    ],
                    groupMeWebhookTab: [
                        fields.config.bot_id,
                        fields.config.api,
                        fields.config.text
                    ],
                    mastodonWebhookTab: [
                        fields.config.instance_url,
                        fields.config.access_token,
                        fields.config.visibility,
                    ],
                    matomoAnalyticsWebhookTab: [
                        fields.config.matomo_url,
                        fields.config.site_id,
                        fields.config.token
                    ],
                    radioDeWebhookTab: [
                        fields.config.broadcastsubdomain,
                        fields.config.apikey
                    ],
                    radioRegWebhookTab: [
                        fields.config.webhookurl,
                        fields.config.apikey
                    ],
                    telegramWebhookTab: [
                        fields.config.bot_token,
                        fields.config.chat_id,
                        fields.config.api,
                        fields.config.text,
                        fields.config.parse_mode
                    ],
                    tuneinWebhookTab: [
                        fields.config.station_id,
                        fields.config.partner_id,
                        fields.config.partner_key
                    ]
                })
            }
        );

        const $reset = () => {
            reset();
            r$.$reset();
        }

        const setType = (newType: WebhookTypes): void => {
            type.value = newType;
            $reset();
        }

        return {
            type,
            setType,
            form,
            r$,
            $reset
        }
    }
);
