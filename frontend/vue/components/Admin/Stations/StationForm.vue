<template>
    <b-overlay variant="card" :show="loading">
        <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

        <b-form class="form vue-form" @submit.prevent="submit">
            <b-tabs :card="!isModal" lazy justified :content-class="tabContentClass">
                <b-tab :title-link-class="getTabClass($v.profileTab)" active>
                    <template #title>
                        <translate key="tab_profile">Profile</translate>
                    </template>

                    <admin-stations-profile-form :form="$v.form" :timezones="timezones"
                                                 :show-advanced="showAdvanced"></admin-stations-profile-form>
                </b-tab>

                <b-tab :title-link-class="getTabClass($v.frontendTab)">
                    <template #title>
                        <translate key="tab_frontend">Broadcasting</translate>
                    </template>

                    <admin-stations-frontend-form :form="$v.form"
                                                  :is-shoutcast-installed="isShoutcastInstalled"
                                                  :countries="countries"
                                                  :show-advanced="showAdvanced"></admin-stations-frontend-form>
                </b-tab>

                <b-tab :title-link-class="getTabClass($v.backendTab)">
                    <template #title>
                        <translate key="tab_backend">AutoDJ</translate>
                    </template>

                    <admin-stations-backend-form :form="$v.form" :station="station"
                                                 :is-stereo-tool-installed="isStereoToolInstalled"
                                                 :show-advanced="showAdvanced"></admin-stations-backend-form>
                </b-tab>

                <b-tab v-if="showAdminTab" :title-link-class="getTabClass($v.adminTab)">
                    <template #title>
                        <translate key="tab_admin">Administration</translate>
                    </template>

                    <admin-stations-admin-form :form="$v.form"
                                               :is-edit-mode="isEditMode"
                                               :storage-location-api-url="storageLocationApiUrl"
                                               :show-advanced="showAdvanced">
                    </admin-stations-admin-form>
                </b-tab>
            </b-tabs>

            <slot name="submitButton">
                <b-card-body body-class="card-padding-sm">
                    <b-button size="lg" type="submit" :variant="(!isValid) ? 'danger' : 'primary'">
                        <slot name="submitButtonText">
                            <translate key="lang_btn_save_changes">Save Changes</translate>
                        </slot>
                    </b-button>
                </b-card-body>
            </slot>
        </b-form>
    </b-overlay>
</template>

<script>
import {validationMixin} from "vuelidate";
import {decimal, numeric, required, url} from 'vuelidate/dist/validators.min.js';
import {AUDIO_PROCESSING_NONE, BACKEND_LIQUIDSOAP, FRONTEND_ICECAST} from "~/components/Entity/RadioAdapters";
import AdminStationsProfileForm from "./Form/ProfileForm";
import AdminStationsFrontendForm from "./Form/FrontendForm";
import AdminStationsBackendForm from "./Form/BackendForm";
import AdminStationsAdminForm from "./Form/AdminForm";
import _ from "lodash";
import mergeExisting from "~/functions/mergeExisting";

export const StationFormProps = {
    props: {
        // Global
        showAdminTab: {
            type: Boolean,
            default: true
        },
        showAdvanced: {
            type: Boolean,
            default: true
        },
        // Profile
        timezones: Object,
        // Frontend
        isShoutcastInstalled: {
            type: Boolean,
            default: false
        },
        isStereoToolInstalled: {
            type: Boolean,
            default: false
        },
        countries: Object,
        // Admin
        storageLocationApiUrl: String
    }
};

export default {
    name: 'AdminStationsForm',
    inheritAttrs: false,
    components: {AdminStationsAdminForm, AdminStationsBackendForm, AdminStationsFrontendForm, AdminStationsProfileForm},
    emits: ['error', 'submitted', 'loadingUpdate', 'validUpdate'],
    props: {
        createUrl: String,
        editUrl: String,
        isEditMode: Boolean,
        isModal: {
            type: Boolean,
            default: false
        }
    },
    mixins: [
        validationMixin,
        StationFormProps
    ],
    validations() {
        let formValidations = {
            form: {
                name: {required},
                description: {},
                genre: {},
                url: {url},
                timezone: {},
                enable_public_page: {},
                enable_on_demand: {},
                enable_hls: {},
                default_album_art_url: {},
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
            },
            profileTab: [
                'form.name', 'form.description', 'form.genre', 'form.url', 'form.timezone', 'form.enable_public_page',
                'form.enable_on_demand', 'form.enable_on_demand_download', 'form.default_album_art_url'
            ],
            frontendTab: [
                'form.frontend_type', 'form.frontend_config'
            ],
            backendTab: [
                'form.backend_type', 'form.backend_config', 'form.enable_requests', 'form.request_delay',
                'form.request_threshold', 'form.enable_streamers', 'form.disconnect_deactivate_streamer'
            ],
        };

        function mergeCustom(objValue, srcValue) {
            if (_.isArray(objValue)) {
                return objValue.concat(srcValue);
            }
        }

        if (this.showAdvanced) {
            const advancedValidations = {
                form: {
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
                },
                profileTab: [
                    'form.short_name', 'form.api_history_items'
                ],
            };

            _.mergeWith(formValidations, advancedValidations, mergeCustom);
        }

        if (this.showAdminTab) {
            const adminValidations = {
                form: {
                    media_storage_location: {},
                    recordings_storage_location: {},
                    podcasts_storage_location: {},
                    is_enabled: {},
                },
                adminTab: [
                    'form.media_storage_location', 'form.recordings_storage_location',
                    'form.podcasts_storage_location', 'form.is_enabled'
                ]
            };

            _.mergeWith(formValidations, adminValidations, mergeCustom);

            if (this.showAdvanced) {
                const advancedAdminValidations = {
                    form: {
                        radio_base_dir: {},
                    },
                    adminTab: [
                        'form.radio_base_dir'
                    ]
                }

                _.mergeWith(formValidations, advancedAdminValidations, mergeCustom);
            }
        }

        return formValidations;
    },
    data() {
        return {
            loading: true,
            error: null,
            form: {},
            station: {},
        };
    },
    watch: {
        loading(newValue) {
            this.$emit('loadingUpdate', newValue);
        },
        isValid(newValue) {
            this.$emit('validUpdate', newValue);
        }
    },
    computed: {
        isValid() {
            return !this.$v.form.$invalid;
        },
        tabContentClass() {
            return (this.isModal)
                ? 'mt-3'
                : '';
        }
    },
    methods: {
        getTabClass(validationGroup) {
            if (!this.loading && validationGroup.$invalid) {
                return 'text-danger';
            }
            return null;
        },
        clear() {
            this.loading = false;
            this.error = null;

            let form = {
                name: '',
                description: '',
                genre: '',
                url: '',
                timezone: 'UTC',
                enable_public_page: true,
                enable_on_demand: false,
                enable_hls: false,
                default_album_art_url: '',
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

            if (this.showAdvanced) {
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
                _.merge(form, advancedForm);
            }

            if (this.showAdminTab) {
                const adminForm = {
                    media_storage_location: '',
                    recordings_storage_location: '',
                    podcasts_storage_location: '',
                    is_enabled: true,
                };
                _.merge(form, adminForm);

                if (this.showAdvanced) {
                    const adminAdvancedForm = {
                        radio_base_dir: '',
                    };
                    _.merge(form, adminAdvancedForm);
                }
            }

            this.station = {
                stereo_tool_configuration_file_path: null,
                links: {
                    stereo_tool_configuration: null
                }
            };
            this.form = form;
        },
        reset() {
            this.clear();
            if (this.isEditMode) {
                this.doLoad();
            }
        },
        doLoad() {
            this.$wrapWithLoading(
                this.axios.get(this.editUrl)
            ).then((resp) => {
                this.populateForm(resp.data);
            }).catch((error) => {
                this.$emit('error', error);
            }).finally(() => {
                this.loading = false;
            });
        },
        populateForm(data) {
            this.form = mergeExisting(this.form, data);
        },
        getSubmittableFormData() {
            return this.form;
        },
        submit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;

            this.$wrapWithLoading(
                this.axios({
                    method: (this.isEditMode)
                        ? 'PUT'
                        : 'POST',
                    url: (this.isEditMode)
                        ? this.editUrl
                        : this.createUrl,
                    data: this.getSubmittableFormData()
                })
            ).then((resp) => {
                this.$notifySuccess();
                this.$emit('submitted');
            }).catch((error) => {
                this.error = error.response.data.message;
            });
        },
    }
}
</script>
