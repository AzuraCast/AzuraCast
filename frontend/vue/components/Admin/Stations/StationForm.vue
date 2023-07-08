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
                <o-tab-item
                    :label="$gettext('Profile')"
                    :item-header-class="getTabClass(v$.$validationGroups.profileTab)"
                    active
                >
                    <admin-stations-profile-form
                        :form="v$"
                        :timezones="timezones"
                        :show-advanced="showAdvanced"
                    />
                </o-tab-item>

                <o-tab-item
                    :label="$gettext('Broadcasting')"
                    :item-header-class="getTabClass(v$.$validationGroups.frontendTab)"
                >
                    <admin-stations-frontend-form
                        :form="v$"
                        :is-shoutcast-installed="isShoutcastInstalled"
                        :countries="countries"
                        :show-advanced="showAdvanced"
                    />
                </o-tab-item>

                <o-tab-item
                    :label="$gettext('AutoDJ')"
                    :item-header-class="getTabClass(v$.$validationGroups.backendTab)"
                >
                    <admin-stations-backend-form
                        :form="v$"
                        :station="station"
                        :is-stereo-tool-installed="isStereoToolInstalled"
                        :show-advanced="showAdvanced"
                    />
                </o-tab-item>

                <o-tab-item
                    :label="$gettext('HLS')"
                    :item-header-class="getTabClass(v$.$validationGroups.hlsTab)"
                >
                    <admin-stations-hls-form
                        :form="v$"
                        :station="station"
                        :show-advanced="showAdvanced"
                    />
                </o-tab-item>

                <o-tab-item
                    :label="$gettext('Song Requests')"
                    :item-header-class="getTabClass(v$.$validationGroups.requestsTab)"
                >
                    <admin-stations-requests-form
                        :form="v$"
                        :station="station"
                        :show-advanced="showAdvanced"
                    />
                </o-tab-item>

                <o-tab-item
                    :label="$gettext('Streamers/DJs')"
                    :item-header-class="getTabClass(v$.$validationGroups.streamersTab)"
                >
                    <admin-stations-streamers-form
                        :form="v$"
                        :station="station"
                        :show-advanced="showAdvanced"
                    />
                </o-tab-item>

                <o-tab-item
                    v-if="showAdminTab"
                    :label="$gettext('Administration')"
                    :item-header-class="getTabClass(v$.$validationGroups.adminTab)"
                >
                    <admin-stations-admin-form
                        :form="v$"
                        :is-edit-mode="isEditMode"
                        :storage-location-api-url="storageLocationApiUrl"
                        :show-advanced="showAdvanced"
                    />
                </o-tab-item>
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
import {decimal, numeric, required, url} from '@vuelidate/validators';
import {
    AUDIO_PROCESSING_NONE,
    BACKEND_LIQUIDSOAP,
    FRONTEND_ICECAST,
    MASTER_ME_PRESET_MUSIC_GENERAL
} from "~/components/Entity/RadioAdapters";
import {computed, ref, watch} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {isArray, merge, mergeWith} from "lodash";
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
    let validations = {
        name: {required},
        description: {},
        genre: {},
        url: {url},
        timezone: {},
        enable_public_page: {},
        enable_on_demand: {},
        enable_hls: {},
        enable_on_demand_download: {},
        frontend_type: {required},
        frontend_config: {
            sc_license_id: {},
            sc_user_id: {},
            source_pw: {},
            admin_pw: {},
        },
        backend_type: {required},
        backend_config: {
            hls_enable_on_public_player: {},
            hls_is_default: {},
            crossfade_type: {},
            crossfade: {decimal},
            audio_processing_method: {},
            post_processing_include_live: {},
            master_me_preset: {},
            master_me_loudness_target: {},
            stereo_tool_license_key: {},
            record_streams: {},
            record_streams_format: {},
            record_streams_bitrate: {},
            dj_buffer: {numeric},
        },
        enable_requests: {},
        request_delay: {numeric},
        request_threshold: {numeric},
        enable_streamers: {},
        disconnect_deactivate_streamer: {},
        $validationGroups: {
            profileTab: [
                'name', 'description', 'genre', 'url', 'timezone', 'enable_public_page',
                'enable_on_demand', 'enable_on_demand_download'
            ],
            frontendTab: [
                'frontend_type', 'frontend_config'
            ],
            backendTab: [
                'backend_type', 'backend_config',
            ],
            hlsTab: [
                'enable_hls',
            ],
            requestsTab: [
                'enable_requests',
                'request_delay',
                'request_threshold'
            ],
            streamersTab: [
                'enable_streamers',
                'disconnect_deactivate_streamer'
            ]
        }
    };

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

    function mergeCustom(objValue, srcValue) {
        if (isArray(objValue)) {
            return objValue.concat(srcValue);
        }
    }

    if (props.showAdvanced) {
        const advancedValidations = {
            short_name: {},
            api_history_items: {},
            frontend_config: {
                port: {numeric},
                max_listeners: {},
                custom_config: {},
                banned_ips: {},
                banned_countries: {},
                allowed_ips: {},
                banned_user_agents: {}
            },
            backend_config: {
                hls_segment_length: {numeric},
                hls_segments_in_playlist: {numeric},
                hls_segments_overhead: {numeric},
                dj_port: {numeric},
                telnet_port: {numeric},
                dj_mount_point: {},
                enable_replaygain_metadata: {},
                autodj_queue_length: {},
                use_manual_autodj: {},
                charset: {},
                performance_mode: {},
                duplicate_prevention_time_range: {},
            },
            $validationGroups: {
                profileTab: [
                    'short_name', 'api_history_items'
                ],
            }
        };

        mergeWith(validations, advancedValidations, mergeCustom);

        const advancedForm = {
            short_name: '',
            api_history_items: 5,
            frontend_config: {
                port: '',
                max_listeners: '',
                custom_config: '',
                banned_ips: '',
                banned_countries: [],
                allowed_ips: '',
                banned_user_agents: '',
            },
            backend_config: {
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

        merge(blankForm, advancedForm);
    }

    if (props.showAdminTab) {
        const adminValidations = {
            media_storage_location: {},
            recordings_storage_location: {},
            podcasts_storage_location: {},
            is_enabled: {},
            $validationGroups: {
                adminTab: [
                    'media_storage_location', 'recordings_storage_location',
                    'podcasts_storage_location', 'is_enabled'
                ]
            }
        };

        mergeWith(validations, adminValidations, mergeCustom);

        const adminForm = {
            media_storage_location: '',
            recordings_storage_location: '',
            podcasts_storage_location: '',
            is_enabled: true,
        };

        merge(blankForm, adminForm);

        if (props.showAdvanced) {
            const advancedAdminValidations = {
                radio_base_dir: {},
                $validationGroups: {
                    adminTab: [
                        'radio_base_dir'
                    ]
                }
            }

            mergeWith(validations, advancedAdminValidations, mergeCustom);

            const adminAdvancedForm = {
                radio_base_dir: '',
            };

            merge(blankForm, adminAdvancedForm);
        }
    }

    return {blankForm, validations};
};

const {blankForm, validations} = buildForm();
const {form, resetForm, v$, ifValid} = useVuelidateOnForm(validations, blankForm);

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

const getTabClass = (validationGroup) => {
    if (!isLoading.value && validationGroup.$invalid) {
        return 'text-danger';
    }
    return null;
}

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


