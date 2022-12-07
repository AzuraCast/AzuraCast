<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <type-select v-if="!type" :webhook-types="webhookTypes" @select="setType"></type-select>
        <b-tabs v-else lazy content-class="mt-3" pills>
            <b-tab active>
                <template #title>
                    <translate key="tab_basic_info">Basic Info</translate>
                </template>

                <basic-info :trigger-options="triggerOptions" :form="$v.form"></basic-info>
            </b-tab>
            <b-tab :title="typeTitle">
                <component :is="formComponent" :now-playing-url="nowPlayingUrl" :form="$v.form"></component>
            </b-tab>
        </b-tabs>

    </modal-form>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import TypeSelect from "./Form/TypeSelect";
import BasicInfo from "./Form/BasicInfo";
import _ from "lodash";
import Generic from "./Form/Generic";
import Email from "./Form/Email";
import Tunein from "./Form/Tunein";
import Discord from "./Form/Discord";
import Telegram from "./Form/Telegram";
import Twitter from "./Form/Twitter";
import GoogleAnalytics from "./Form/GoogleAnalytics";
import MatomoAnalytics from "./Form/MatomoAnalytics";
import Mastodon from "./Form/Mastodon";

export default {
    name: 'EditModal',
    components: {BasicInfo, TypeSelect},
    mixins: [BaseEditModal],
    props: {
        nowPlayingUrl: String,
        webhookTypes: Object,
        triggerTitles: Object,
        triggerDescriptions: Object
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
            validations.form.config = _.get(this.webhookConfig, [this.type, 'validations'], {});
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

            let webhookKeys = _.get(this.webhookTypes, [this.type, 'triggers'], []);
            return _.map(webhookKeys, (key) => {
                return {
                    html:
                        '<h6 class="font-weight-bold mb-0">' + this.triggerTitles[key] + '</h6>'
                        + '<p class="card-text small">' + this.triggerDescriptions[key] + '</p>',
                    value: key
                };
            });
        },
        typeTitle() {
            return _.get(this.webhookTypes, [this.type, 'name'], '');
        },
        formComponent() {
            return _.get(this.webhookConfig, [this.type, 'component'], Generic);
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
            let msg = this.$gettext('Now playing on %{ station }:');
            return this.$gettextInterpolate(msg, {'station': '{{ station.name }}'});
        },
        langTelegramDefaultContent() {
            let msg = this.$gettext('Now playing on %{ station }: %{ title } by %{ artist }! Tune in now.');
            return this.$gettextInterpolate(msg, {
                station: '{{ station.name }}',
                title: '{{ now_playing.song.title }}',
                artist: '{{ now_playing.song.artist }}'
            });
        },
        langTwitterDefaultMessage() {
            let msg = this.$gettext('Now playing on %{ station }: %{ title } by %{ artist }! Tune in now: %{ url }');
            return this.$gettextInterpolate(msg, {
                station: '{{ station.name }}',
                title: '{{ now_playing.song.title }}',
                artist: '{{ now_playing.song.artist }}',
                url: '{{ station.public_player_url }}'
            });
        },
        langTwitterSongChangedLiveMessage() {
            let msg = this.$gettext('Now playing on %{ station }: %{ title } by %{ artist } with your host, %{ dj }! Tune in now: %{ url }');
            return this.$gettextInterpolate(msg, {
                station: '{{ station.name }}',
                title: '{{ now_playing.song.title }}',
                artist: '{{ now_playing.song.artist }}',
                dj: '{{ live.streamer_name }}',
                url: '{{ station.public_player_url }}'
            });
        },
        langTwitterDjOnMessage() {
            let msg = this.$gettext('%{ dj } is now live on %{ station }! Tune in now: %{ url }');
            return this.$gettextInterpolate(msg, {
                dj: '{{ live.streamer_name }}',
                station: '{{ station.name }}',
                url: '{{ station.public_player_url }}'
            });
        },
        langTwitterDjOffMessage() {
            let msg = this.$gettext('Thanks for listening to %{ station }!');
            return this.$gettextInterpolate(msg, {
                station: '{{ station.name }}',
            });
        },
        langTwitterStationOfflineMessage() {
            let msg = this.$gettext('%{ station } is going offline for now.');
            return this.$gettextInterpolate(msg, {
                station: '{{ station.name }}'
            });
        },
        langTwitterStationOnlineMessage() {
            let msg = this.$gettext('%{ station } is back online! Tune in now: %{ url }');
            return this.$gettextInterpolate(msg, {
                station: '{{ station.name }}',
                url: '{{ station.public_player_url }}'
            });
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
            this.form.config = _.get(this.webhookConfig, [type, 'defaultConfig'], {});
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
