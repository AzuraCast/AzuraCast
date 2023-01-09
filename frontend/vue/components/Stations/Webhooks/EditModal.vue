<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
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
                    :form="v$"
                />
            </b-tab>
            <b-tab :title="typeTitle">
                <component
                    :is="formComponent"
                    :now-playing-url="nowPlayingUrl"
                    :form="v$"
                />
            </b-tab>
        </b-tabs>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
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
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
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
});

const emit = defineEmits(['relist']);

const type = ref(null);

const $modal = ref(); // Template Ref

const {$gettext} = useTranslate();

const langPoweredByAzuraCast = $gettext('Powered by AzuraCast');

const langDiscordDefaultContent = $gettext(
    'Now playing on %{ station }:',
    {'station': '{{ station.name }}'}
);

const langTelegramDefaultContent = $gettext(
    'Now playing on %{ station }: %{ title } by %{ artist }! Tune in now.',
    {
        station: '{{ station.name }}',
        title: '{{ now_playing.song.title }}',
        artist: '{{ now_playing.song.artist }}'
    }
);

const langTwitterDefaultMessage = $gettext(
    'Now playing on %{ station }: %{ title } by %{ artist }! Tune in now: %{ url }',
    {
        station: '{{ station.name }}',
        title: '{{ now_playing.song.title }}',
        artist: '{{ now_playing.song.artist }}',
        url: '{{ station.public_player_url }}'
    }
);

const langTwitterSongChangedLiveMessage = $gettext(
    'Now playing on %{ station }: %{ title } by %{ artist } with your host, %{ dj }! Tune in now: %{ url }',
    {
        station: '{{ station.name }}',
        title: '{{ now_playing.song.title }}',
        artist: '{{ now_playing.song.artist }}',
        dj: '{{ live.streamer_name }}',
        url: '{{ station.public_player_url }}'
    }
);

const langTwitterDjOnMessage = $gettext(
    '%{ dj } is now live on %{ station }! Tune in now: %{ url }',
    {
        dj: '{{ live.streamer_name }}',
        station: '{{ station.name }}',
        url: '{{ station.public_player_url }}'
    }
);

const langTwitterDjOffMessage = $gettext(
    'Thanks for listening to %{ station }!',
    {
        station: '{{ station.name }}',
    }
);

const langTwitterStationOfflineMessage = $gettext(
    '%{ station } is going offline for now.',
    {
        station: '{{ station.name }}'
    }
);

const langTwitterStationOnlineMessage = $gettext(
    '%{ station } is back online! Tune in now: %{ url }',
    {
        station: '{{ station.name }}',
        url: '{{ station.public_player_url }}'
    }
);

const webhookConfig = {
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
            content: langDiscordDefaultContent,
            title: '{{ now_playing.song.title }}',
            description: '{{ now_playing.song.artist }}',
            url: '{{ station.listen_url }}',
            author: '{{ live.streamer_name }}',
            thumbnail: '{{ now_playing.song.art }}',
            footer: langPoweredByAzuraCast,
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
            text: langTelegramDefaultContent,
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
            message: langTwitterDefaultMessage,
            message_song_changed_live: langTwitterSongChangedLiveMessage,
            message_live_connect: langTwitterDjOnMessage,
            message_live_disconnect: langTwitterDjOffMessage,
            message_station_offline: langTwitterStationOfflineMessage,
            message_station_online: langTwitterStationOnlineMessage
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
            message: langTwitterDefaultMessage,
            message_song_changed_live: langTwitterSongChangedLiveMessage,
            message_live_connect: langTwitterDjOnMessage,
            message_live_disconnect: langTwitterDjOffMessage,
            message_station_offline: langTwitterStationOfflineMessage,
            message_station_online: langTwitterStationOnlineMessage
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

const triggerOptions = computed(() => {
    if (!type.value) {
        return [];
    }

    let webhookKeys = get(props.webhookTypes, [type.value, 'triggers'], []);
    return map(webhookKeys, (key) => {
        return {
            html:
                '<h6 class="font-weight-bold mb-0">' + props.triggerTitles[key] + '</h6>'
                + '<p class="card-text small">' + props.triggerDescriptions[key] + '</p>',
            value: key
        };
    });
});

const typeTitle = computed(() => {
    return get(props.webhookTypes, [type.value, 'name'], '');
});

const formComponent = computed(() => {
    return get(webhookConfig, [type.value, 'component'], Generic);
});

const {
    loading,
    error,
    isEditMode,
    v$,
    resetForm,
    clearContents: originalClearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    props,
    emit,
    $modal,
    () => computed(() => {
        let validations = {
            name: {required},
            triggers: {},
            config: {}
        };

        const triggerOptionsValue = triggerOptions.value;
        if (triggerOptionsValue.length > 0) {
            validations.triggers = {required};
        }

        if (type.value !== null) {
            validations.config = get(
                webhookConfig,
                [type.value, 'validations'],
                {}
            );
        }

        return validations;
    }),
    () => computed(() => {
        let newForm = {
            name: null,
            triggers: [],
            config: {}
        };

        if (type.value !== null) {
            newForm.config = get(
                webhookConfig,
                [type.value, 'defaultConfig'],
                {}
            );
        }

        return newForm;
    }),
    {
        populateForm: (data, formRef) => {
            type.value = data.type;
            formRef.value = {
                name: data.name,
                triggers: data.triggers,
                config: data.config
            };
        },
        getSubmittableFormData(formRef, isEditModeRef) {
            let formData = formRef.value;
            if (!isEditModeRef.value) {
                formData.type = type.value;
            }
            return formData;
        },
    }
);

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Web Hook')
        : $gettext('Add Web Hook');
});

const clearContents = () => {
    type.value = null;
    originalClearContents();
};

const setType = (newType) => {
    type.value = newType;
    resetForm();
};

defineExpose({
    create,
    edit,
    close
});
</script>
