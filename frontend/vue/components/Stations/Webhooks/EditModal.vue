<template>
    <modal-form
        ref="modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.form.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <type-select
            v-if="!type"
            :webhook-types="webhookTypes"
            @select="setType"
        />
        <b-tabs
            v-else
            lazy
            content-class="mt-3"
            pills
        >
            <b-tab active>
                <template #title>
                    {{ $gettext('Basic Info') }}
                </template>

                <basic-info
                    :trigger-options="triggerOptions"
                    :form="v$.form"
                />
            </b-tab>
            <b-tab :title="typeTitle">
                <component
                    :is="formComponent"
                    :now-playing-url="nowPlayingUrl"
                    :form="v$.form"
                />
            </b-tab>
        </b-tabs>
    </modal-form>
</template>
<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import TypeSelect from "./Form/TypeSelect";
import BasicInfo from "./Form/BasicInfo";
import {get, map} from "lodash";
import Generic from "./Form/Generic";
import Email from "./Form/Email";
import Tunein from "./Form/Tunein";
import Discord from "./Form/Discord";
import Telegram from "./Form/Telegram";
import Twitter from "./Form/Twitter";
import GoogleAnalytics from "./Form/GoogleAnalytics";
import MatomoAnalytics from "./Form/MatomoAnalytics";
import Mastodon from "./Form/Mastodon";
import useVuelidate from "@vuelidate/core";

/* TODO Options API */

export default {
    name: 'EditModal',
    components: {BasicInfo, TypeSelect},
    mixins: [BaseEditModal],
    props: {
        nowPlayingUrl: {
            type: String,
            required: true
        },
        webhookTypes: {
            type: Object,
            required: true
        },
        triggerTitles: {
            type: Object,
            required: true
        },
        triggerDescriptions: {
            type: Object,
            required: true
        }
    },
    setup() {
        return {v$: useVuelidate()}
    },
    data() {
        return {
            type: null,
        }
    },
    validations() {
        let validations = {
            type: {required},
            form: {
                name: {required},
                triggers: {},
                config: {}
            }
        };

        if (this.triggerOptions.length > 0) {
            validations.form.triggers = {required};
        }

        if (this.type !== null) {
            validations.form.config = get(this.webhookConfig, [this.type, 'validations'], {});
        }

        return validations;
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Web Hook')
                : this.$gettext('Add Web Hook');
        },
        triggerOptions() {
            if (!this.type) {
                return [];
            }

            let webhookKeys = get(this.webhookTypes, [this.type, 'triggers'], []);
            return map(webhookKeys, (key) => {
                return {
                    html:
                        '<h6 class="font-weight-bold mb-0">' + this.triggerTitles[key] + '</h6>'
                        + '<p class="card-text small">' + this.triggerDescriptions[key] + '</p>',
                    value: key
                };
            });
        },
        typeTitle() {
            return get(this.webhookTypes, [this.type, 'name'], '');
        },
        formComponent() {
            return get(this.webhookConfig, [this.type, 'component'], Generic);
        },
        webhookConfig() {
            return {
                'generic': {
                    component: Generic,
                    validations: {
                        webhook_url: {required},
                        basic_auth_username: {},
                        basic_auth_password: {},
                        timeout: {},
                    },
                    defaultConfig: {
                        webhook_url: '',
                        basic_auth_username: '',
                        basic_auth_password: '',
                        timeout: '5',
                    }
                },
                'email': {
                    component: Email,
                    validations: {
                        to: {required},
                        subject: {required},
                        message: {required}
                    },
                    defaultConfig: {
                        to: '',
                        subject: '',
                        message: ''
                    }
                },
                'tunein': {
                    component: Tunein,
                    validations: {
                        station_id: {required},
                        partner_id: {required},
                        partner_key: {required},
                    },
                    defaultConfig: {
                        station_id: '',
                        partner_id: '',
                        partner_key: ''
                    }
                },
                'discord': {
                    component: Discord,
                    validations: {
                        webhook_url: {required},
                        content: {},
                        title: {},
                        description: {},
                        url: {},
                        author: {},
                        thumbnail: {},
                        footer: {},
                    },
                    defaultConfig: {
                        webhook_url: '',
                        content: this.langDiscordDefaultContent,
                        title: '{{ now_playing.song.title }}',
                        description: '{{ now_playing.song.artist }}',
                        url: '{{ station.listen_url }}',
                        author: '{{ live.streamer_name }}',
                        thumbnail: '{{ now_playing.song.art }}',
                        footer: this.langPoweredByAzuraCast,
                    }
                },
                'telegram': {
                    component: Telegram,
                    validations: {
                        bot_token: {required},
                        chat_id: {required},
                        api: {},
                        text: {required},
                        parse_mode: {required}
                    },
                    defaultConfig: {
                        bot_token: '',
                        chat_id: '',
                        api: '',
                        text: this.langTelegramDefaultContent,
                        parse_mode: 'Markdown'
                    }
                },
                'twitter': {
                    component: Twitter,
                    validations: {
                        consumer_key: {required},
                        consumer_secret: {required},
                        token: {required},
                        token_secret: {required},
                        rate_limit: {},
                        message: {},
                        message_song_changed_live: {},
                        message_live_connect: {},
                        message_live_disconnect: {},
                        message_station_offline: {},
                        message_station_online: {}
                    },
                    defaultConfig: {
                        consumer_key: '',
                        consumer_secret: '',
                        token: '',
                        token_secret: '',
                        rate_limit: 0,
                        message: this.langTwitterDefaultMessage,
                        message_song_changed_live: this.langTwitterSongChangedLiveMessage,
                        message_live_connect: this.langTwitterDjOnMessage,
                        message_live_disconnect: this.langTwitterDjOffMessage,
                        message_station_offline: this.langTwitterStationOfflineMessage,
                        message_station_online: this.langTwitterStationOnlineMessage
                    }
                },
                'mastodon': {
                    component: Mastodon,
                    validations: {
                        instance_url: {required},
                        access_token: {required},
                        rate_limit: {},
                        visibility: {required},
                        message: {},
                        message_song_changed_live: {},
                        message_live_connect: {},
                        message_live_disconnect: {},
                        message_station_offline: {},
                        message_station_online: {}
                    },
                    defaultConfig: {
                        instance_url: '',
                        access_token: '',
                        rate_limit: 0,
                        visibility: 'public',
                        message: this.langTwitterDefaultMessage,
                        message_song_changed_live: this.langTwitterSongChangedLiveMessage,
                        message_live_connect: this.langTwitterDjOnMessage,
                        message_live_disconnect: this.langTwitterDjOffMessage,
                        message_station_offline: this.langTwitterStationOfflineMessage,
                        message_station_online: this.langTwitterStationOnlineMessage
                    }
                },
                'google_analytics': {
                    component: GoogleAnalytics,
                    validations: {
                        tracking_id: {required}
                    },
                    defaultConfig: {
                        tracking_id: ''
                    }
                },
                'matomo_analytics': {
                    component: MatomoAnalytics,
                    validations: {
                        matomo_url: {required},
                        site_id: {required},
                        token: {},
                    },
                    defaultConfig: {
                        matomo_url: '',
                        site_id: '',
                        token: ''
                    }
                }
            };
        },
        langPoweredByAzuraCast() {
            return this.$gettext('Powered by AzuraCast');
        },
        langDiscordDefaultContent() {
            return this.$gettext(
                'Now playing on %{ station }:',
                {'station': '{{ station.name }}'}
            );
        },
        langTelegramDefaultContent() {
            return this.$gettext(
                'Now playing on %{ station }: %{ title } by %{ artist }! Tune in now.',
                {
                    station: '{{ station.name }}',
                    title: '{{ now_playing.song.title }}',
                    artist: '{{ now_playing.song.artist }}'
                }
            );
        },
        langTwitterDefaultMessage() {
            return this.$gettext(
                'Now playing on %{ station }: %{ title } by %{ artist }! Tune in now: %{ url }',
                {
                    station: '{{ station.name }}',
                    title: '{{ now_playing.song.title }}',
                    artist: '{{ now_playing.song.artist }}',
                    url: '{{ station.public_player_url }}'
                }
            );
        },
        langTwitterSongChangedLiveMessage() {
            return this.$gettext(
                'Now playing on %{ station }: %{ title } by %{ artist } with your host, %{ dj }! Tune in now: %{ url }',
                {
                    station: '{{ station.name }}',
                    title: '{{ now_playing.song.title }}',
                    artist: '{{ now_playing.song.artist }}',
                    dj: '{{ live.streamer_name }}',
                    url: '{{ station.public_player_url }}'
                }
            );
        },
        langTwitterDjOnMessage() {
            return this.$gettext(
                '%{ dj } is now live on %{ station }! Tune in now: %{ url }',
                {
                    dj: '{{ live.streamer_name }}',
                    station: '{{ station.name }}',
                    url: '{{ station.public_player_url }}'
                }
            );
        },
        langTwitterDjOffMessage() {
            return this.$gettext(
                'Thanks for listening to %{ station }!',
                {
                    station: '{{ station.name }}',
                }
            );
        },
        langTwitterStationOfflineMessage() {
            return this.$gettext(
                '%{ station } is going offline for now.',
                {
                    station: '{{ station.name }}'
                }
            );
        },
        langTwitterStationOnlineMessage() {
            return this.$gettext(
                '%{ station } is back online! Tune in now: %{ url }',
                {
                    station: '{{ station.name }}',
                    url: '{{ station.public_player_url }}'
                }
            );
        }
    },
    methods: {
        resetForm() {
            this.type = null;
            this.form = {
                name: null,
                triggers: [],
                config: {}
            };
        },
        setType(type) {
            this.type = type;
            this.form.config = get(this.webhookConfig, [type, 'defaultConfig'], {});
        },
        getSubmittableFormData() {
            let formData = this.form;
            if (!this.isEditMode) {
                formData.type = this.type;
            }
            return formData;
        },
        populateForm(d) {
            this.type = d.type;
            this.form = {
                name: d.name,
                triggers: d.triggers,
                config: d.config
            };
        }
    }
};
</script>
