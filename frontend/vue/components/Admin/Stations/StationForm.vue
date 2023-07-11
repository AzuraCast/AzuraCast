<template>
    <loading :loading="isLoading">
        <div
            v-show="error != null"
            class="alert alert-danger"
        >
            {{ error }}
        </div>

        <form
            class="form vue-form"
            @submit.prevent="submit"
        >
            <o-tabs
                nav-tabs-class="nav-tabs"
                content-class="mt-3"
            >
                <admin-stations-profile-form
                    v-model:form="form"
                    :timezones="timezones"
                    :show-advanced="showAdvanced"
                />

                <admin-stations-frontend-form
                    v-model:form="form"
                    :is-shoutcast-installed="isShoutcastInstalled"
                    :countries="countries"
                    :show-advanced="showAdvanced"
                />

                <admin-stations-backend-form
                    v-model:form="form"
                    :station="station"
                    :is-stereo-tool-installed="isStereoToolInstalled"
                    :show-advanced="showAdvanced"
                />

                <admin-stations-hls-form
                    v-model:form="form"
                    :station="station"
                    :show-advanced="showAdvanced"
                />

                <admin-stations-requests-form
                    v-model:form="form"
                    :station="station"
                    :show-advanced="showAdvanced"
                />

                <admin-stations-streamers-form
                    v-model:form="form"
                    :station="station"
                    :show-advanced="showAdvanced"
                />

                <admin-stations-admin-form
                    v-if="showAdminTab"
                    v-model:form="form"
                    :is-edit-mode="isEditMode"
                    :storage-location-api-url="storageLocationApiUrl"
                    :show-advanced="showAdvanced"
                />
            </o-tabs>

            <slot name="submitButton">
                <div class="buttons mt-3">
                    <button
                        type="submit"
                        class="btn btn-lg"
                        :class="(!isValid) ? 'btn-danger' : 'btn-primary'"
                    >
                        <slot name="submitButtonText">
                            {{ $gettext('Save Changes') }}
                        </slot>
                    </button>
                </div>
            </slot>
        </form>
    </loading>
</template>

<script setup>
import AdminStationsProfileForm from "./Form/ProfileForm.vue";
import AdminStationsFrontendForm from "./Form/FrontendForm.vue";
import AdminStationsBackendForm from "./Form/BackendForm.vue";
import AdminStationsAdminForm from "./Form/AdminForm.vue";
import AdminStationsHlsForm from "./Form/HlsForm.vue";
import AdminStationsRequestsForm from "./Form/RequestsForm.vue";
import AdminStationsStreamersForm from "./Form/StreamersForm.vue";
import {
    AUDIO_PROCESSING_NONE,
    BACKEND_LIQUIDSOAP,
    FRONTEND_ICECAST, MASTER_ME_PRESET_MUSIC_GENERAL,
} from "~/components/Entity/RadioAdapters";
import {computed, ref, watch} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";
import {useResettableRef} from "~/functions/useResettableRef";
import Loading from '~/components/Common/Loading';

const props = defineProps({
    ...stationFormProps,
    createUrl: {
        type: String,
        default: null
    },
    editUrl: {
        type: String,
        default: null
    },
    isEditMode: {
        type: Boolean,
        required: true
    },
    isModal: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['error', 'submitted', 'loadingUpdate', 'validUpdate']);

const buildForm = () => {
    let blankForm = {
        name: '',
        description: '',
        genre: '',
        url: '',
        timezone: 'UTC',
        enable_public_page: true,
        enable_on_demand: false,
        enable_hls: false,
        enable_on_demand_download: true,
        frontend_type: FRONTEND_ICECAST,
        frontend_config: {
            sc_license_id: '',
            sc_user_id: '',
            source_pw: '',
            admin_pw: '',
        },
        backend_type: BACKEND_LIQUIDSOAP,
        backend_config: {
            hls_enable_on_public_player: false,
            hls_is_default: false,
            crossfade_type: 'normal',
            crossfade: 2,
            audio_processing_method: AUDIO_PROCESSING_NONE,
            post_processing_include_live: true,
            master_me_preset: MASTER_ME_PRESET_MUSIC_GENERAL,
            master_me_loudness_target: -16,
            stereo_tool_license_key: '',
            record_streams: false,
            record_streams_format: 'mp3',
            record_streams_bitrate: 128,
            dj_buffer: 5,
        },
        enable_requests: false,
        request_delay: 5,
        request_threshold: 15,
        enable_streamers: false,
        disconnect_deactivate_streamer: 0,
    };

    if (props.showAdvanced) {
        blankForm = {
            ...blankForm,
            short_name: '',
            api_history_items: 5,
            frontend_config: {
                ...blankForm.frontend_config,
                port: '',
                max_listeners: '',
                custom_config: '',
                banned_ips: '',
                banned_countries: [],
                allowed_ips: '',
                banned_user_agents: '',
            },
            backend_config: {
                ...blankForm.backend_config,
                hls_segment_length: 4,
                hls_segments_in_playlist: 5,
                hls_segments_overhead: 2,
                dj_port: '',
                telnet_port: '',
                dj_mount_point: '/',
                enable_replaygain_metadata: false,
                autodj_queue_length: 3,
                use_manual_autodj: false,
                charset: 'UTF-8',
                performance_mode: 'disabled',
                duplicate_prevention_time_range: 120,
            },
        };
    }

    if (props.showAdminTab) {
        blankForm = {
            ...blankForm,
            media_storage_location: '',
            recordings_storage_location: '',
            podcasts_storage_location: '',
            is_enabled: true,
        };

        if (props.showAdvanced) {
            blankForm = {
                ...blankForm,
                radio_base_dir: '',
            };
        }
    }

    return blankForm;
};

const {form, resetForm, v$, ifValid} = useVuelidateOnForm({}, buildForm());

const isValid = computed(() => {
    return !v$.value?.$invalid ?? true;
});

watch(isValid, (newValue) => {
    emit('validUpdate', newValue);
});

const isLoading = ref(true);

watch(isLoading, (newValue) => {
    emit('loadingUpdate', newValue);
});

const error = ref(null);

const blankStation = {
    stereo_tool_configuration_file_path: null,
    links: {
        stereo_tool_configuration: null
    }
};

const {record: station, reset: resetStation} = useResettableRef(blankStation);

const clear = () => {
    resetForm();
    resetStation();

    isLoading.value = false;
    error.value = null;
};

const populateForm = (data) => {
    form.value = mergeExisting(form.value, data);
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doLoad = () => {
    isLoading.value = true;

    wrapWithLoading(
        axios.get(props.editUrl)
    ).then((resp) => {
        populateForm(resp.data);
    }).catch((err) => {
        emit('error', err);
    }).finally(() => {
        isLoading.value = false;
    });
};

const reset = () => {
    clear();
    if (props.isEditMode) {
        doLoad();
    }
};

const submit = () => {
    ifValid(() => {
        error.value = null;
        wrapWithLoading(
            axios({
                method: (props.isEditMode)
                    ? 'PUT'
                    : 'POST',
                url: (props.isEditMode)
                    ? props.editUrl
                    : props.createUrl,
                data: form.value
            })
        ).then(() => {
            notifySuccess();
            emit('submitted');
        }).catch((err) => {
            error.value = err.response.data.message;
        });
    });
};

defineExpose({
    reset,
    submit
});
</script>
