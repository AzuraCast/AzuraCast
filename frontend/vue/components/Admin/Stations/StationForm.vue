<template>
    <b-overlay variant="card" :show="loading">
        <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

        <b-form class="form vue-form" @submit.prevent="submit">
            <b-tabs :card="!isModal" lazy justified :content-class="tabContentClass">
                <admin-stations-profile-form :form="$v.form" :tab-class="getTabClass($v.profileTab)"
                                             :timezones="timezones"></admin-stations-profile-form>
                <admin-stations-frontend-form :form="$v.form" :tab-class="getTabClass($v.frontendTab)"
                                              :is-shoutcast-installed="isShoutcastInstalled"
                                              :countries="countries"></admin-stations-frontend-form>
                <admin-stations-backend-form :form="$v.form"
                                             :tab-class="getTabClass($v.backendTab)"></admin-stations-backend-form>
                <admin-stations-admin-form v-if="showAdminTab" :tab-class="getTabClass($v.adminTab)" :form="$v.form"
                                           :is-edit-mode="isEditMode" :storage-location-api-url="storageLocationApiUrl">
                </admin-stations-admin-form>
            </b-tabs>

            <slot name="submitButton">
                <b-card-body body-class="card-padding-sm">
                    <b-button size="lg" type="submit" variant="primary" :disabled="!isValid">
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
import {required} from 'vuelidate/dist/validators.min.js';
import {BACKEND_LIQUIDSOAP, FRONTEND_ICECAST} from "~/components/Entity/RadioAdapters";
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
        // Profile
        timezones: Object,
        // Frontend
        isShoutcastInstalled: {
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
                url: {},
                timezone: {},
                enable_public_page: {},
                enable_on_demand: {},
                default_album_art_url: {},
                enable_on_demand_download: {},
                short_name: {},
                api_history_items: {},
                frontend_type: {required},
                frontend_config: {
                    source_pw: {},
                    admin_pw: {},
                    port: {},
                    max_listeners: {},
                    custom_config: {},
                    banned_ips: {},
                    banned_countries: {},
                    allowed_ips: {}
                },
                backend_type: {required},
                backend_config: {
                    crossfade_type: {},
                    crossfade: {},
                    nrj: {},
                    record_streams: {},
                    record_streams_format: {},
                    record_streams_bitrate: {},
                    dj_port: {},
                    telnet_port: {},
                    dj_buffer: {},
                    dj_mount_point: {},
                    enable_replaygain_metadata: {},
                    autodj_queue_length: {},
                    use_manual_autodj: {},
                    charset: {},
                    duplicate_prevention_time_range: {},
                },
                enable_requests: {},
                request_delay: {},
                request_threshold: {},
                enable_streamers: {},
                disconnect_deactivate_streamer: {},
            },
            profileTab: [
                'form.name', 'form.description', 'form.genre', 'form.url', 'form.timezone', 'form.enable_public_page',
                'form.enable_on_demand', 'form.enable_on_demand_download', 'form.default_album_art_url',
                'form.short_name', 'form.api_history_items'
            ],
            frontendTab: [
                'form.frontend_type', 'form.frontend_config'
            ],
            backendTab: [
                'form.backend_type', 'form.backend_config', 'form.enable_requests', 'form.request_delay',
                'form.request_threshold', 'form.enable_streamers', 'form.disconnect_deactivate_streamer'
            ],
        };

        if (this.showAdminTab) {
            let adminValidations = {
                form: {
                    media_storage_location_id: {},
                    recordings_storage_location_id: {},
                    podcasts_storage_location_id: {},
                    is_enabled: {},
                    radio_base_dir: {},
                },
                adminTab: [
                    'form.media_storage_location_id', 'form.recordings_storage_location_id',
                    'form.podcasts_storage_location_id', 'form.is_enabled', 'form.radio_base_dir'
                ]
            };

            _.merge(formValidations, adminValidations);
        }

        return formValidations;
    },
    data() {
        return {
            loading: true,
            error: null,
            form: {}
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
                default_album_art_url: '',
                enable_on_demand_download: true,
                short_name: '',
                api_history_items: 5,
                frontend_type: FRONTEND_ICECAST,
                frontend_config: {
                    source_pw: '',
                    admin_pw: '',
                    port: '',
                    max_listeners: '',
                    custom_config: '',
                    banned_ips: '',
                    banned_countries: [],
                    allowed_ips: ''
                },
                backend_type: BACKEND_LIQUIDSOAP,
                backend_config: {
                    crossfade_type: 'normal',
                    crossfade: 2,
                    nrj: false,
                    record_streams: false,
                    record_streams_format: 'mp3',
                    record_streams_bitrate: 128,
                    dj_port: '',
                    telnet_port: '',
                    dj_buffer: 5,
                    dj_mount_point: '/',
                    enable_replaygain_metadata: false,
                    autodj_queue_length: 3,
                    use_manual_autodj: false,
                    charset: 'UTF-8',
                    duplicate_prevention_time_range: 120,
                },
                enable_requests: false,
                request_delay: 5,
                request_threshold: 15,
                enable_streamers: false,
                disconnect_deactivate_streamer: 0,
            };

            if (this.showAdminTab) {
                let adminForm = {
                    media_storage_location_id: '',
                    recordings_storage_location_id: '',
                    podcasts_storage_location_id: '',
                    is_enabled: true,
                    radio_base_dir: '',
                };

                form = {...form, ...adminForm};
            }

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
