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
            :type-details="typeDetails"
            @select="setType"
        />

        <o-tabs
            v-else
            nav-tabs-class="nav-tabs"
            content-class="mt-3"
            destroy-on-hide
        >
            <basic-info
                v-model:form="form"
                :trigger-details="triggerDetails"
                :triggers="triggers"
            />

            <component
                :is="formComponent"
                v-model:form="form"
                :label="typeTitle"
            />
        </o-tabs>
    </modal-form>
</template>

<script setup>
import TypeSelect from "./Form/TypeSelect";
import BasicInfo from "./Form/BasicInfo";
import {get, map} from "lodash";
import Generic from "./Form/Generic";
import Email from "./Form/Email";
import Tunein from "./Form/Tunein";
import Discord from "./Form/Discord";
import Telegram from "./Form/Telegram";
import Twitter from "./Form/Twitter";
import GoogleAnalyticsV3 from "./Form/GoogleAnalyticsV3";
import GoogleAnalyticsV4 from "./Form/GoogleAnalyticsV4";
import MatomoAnalytics from "./Form/MatomoAnalytics";
import Mastodon from "./Form/Mastodon";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, provide, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import {
    getTriggers,
    WEBHOOK_TYPE_DISCORD,
    WEBHOOK_TYPE_EMAIL,
    WEBHOOK_TYPE_GENERIC,
    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3,
    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4,
    WEBHOOK_TYPE_MASTODON, WEBHOOK_TYPE_MATOMO_ANALYTICS,
    WEBHOOK_TYPE_TELEGRAM,
    WEBHOOK_TYPE_TUNEIN,
    WEBHOOK_TYPE_TWITTER
} from "~/components/Entity/Webhooks";

const props = defineProps({
    ...baseEditModalProps,
    nowPlayingUrl: {
        type: String,
        required: true
    },
    typeDetails: {
        type: Object,
        required: true
    },
    triggerDetails: {
        type: Object,
        required: true
    }
});

provide('nowPlayingUrl', props.nowPlayingUrl);

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

const defaultMessages = {
    message: langTwitterDefaultMessage,
    message_song_changed_live: langTwitterSongChangedLiveMessage,
    message_live_connect: langTwitterDjOnMessage,
    message_live_disconnect: langTwitterDjOffMessage,
    message_station_offline: langTwitterStationOfflineMessage,
    message_station_online: langTwitterStationOnlineMessage
};

const webhookConfig = {
    [WEBHOOK_TYPE_GENERIC]: {
        component: Generic,
        defaultConfig: {
            webhook_url: '',
            basic_auth_username: '',
            basic_auth_password: '',
            timeout: '5',
        }
    },
    [WEBHOOK_TYPE_EMAIL]: {
        component: Email,
        defaultConfig: {
            to: '',
            subject: '',
            message: ''
        }
    },
    [WEBHOOK_TYPE_TUNEIN]: {
        component: Tunein,
        defaultConfig: {
            station_id: '',
            partner_id: '',
            partner_key: ''
        }
    },
    [WEBHOOK_TYPE_DISCORD]: {
        component: Discord,
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
    [WEBHOOK_TYPE_TELEGRAM]: {
        component: Telegram,
        defaultConfig: {
            bot_token: '',
            chat_id: '',
            api: '',
            text: langTelegramDefaultContent,
            parse_mode: 'Markdown'
        }
    },
    [WEBHOOK_TYPE_TWITTER]: {
        component: Twitter,
        defaultConfig: {
            consumer_key: '',
            consumer_secret: '',
            token: '',
            token_secret: '',
            rate_limit: 0,
            ...defaultMessages
        }
    },
    [WEBHOOK_TYPE_MASTODON]: {
        component: Mastodon,
        defaultConfig: {
            instance_url: '',
            access_token: '',
            rate_limit: 0,
            visibility: 'public',
            ...defaultMessages
        }
    },
    [WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3]: {
        component: GoogleAnalyticsV3,
        defaultConfig: {
            tracking_id: ''
        }
    },
    [WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4]: {
        component: GoogleAnalyticsV4,
        defaultConfig: {
            api_secret: '',
            measurement_id: ''
        }
    },
    [WEBHOOK_TYPE_MATOMO_ANALYTICS]: {
        component: MatomoAnalytics,
        defaultConfig: {
            matomo_url: '',
            site_id: '',
            token: ''
        }
    }
};

const triggers = computed(() => {
    if (!type.value) {
        return [];
    }

    return map(
        getTriggers(type.value),
        (trigger) => {
            return {
                key: trigger,
                title: get(props.triggerDetails, [trigger, 'title']),
                description: get(props.triggerDetails, [trigger, 'description'])
            };
        }
    );
});

const typeTitle = computed(() => {
    return get(props.typeDetails, [type.value, 'title'], '');
});

const formComponent = computed(() => {
    return get(webhookConfig, [type.value, 'component'], Generic);
});

const {
    loading,
    error,
    isEditMode,
    form,
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
    {},
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
    if (isEditMode.value) {
        return $gettext('Edit Web Hook');
    }

    return type.value
        ? $gettext('Add Web Hook')
        : $gettext('Select Web Hook Type');
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
