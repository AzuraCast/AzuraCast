<template>
    <b-overlay variant="card" :show="loading">
        <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

        <b-form class="form vue-form" @submit.prevent="submit">
            <b-tabs card lazy justified>
                <admin-stations-profile-form :form="$v.form" :timezones="timezones"></admin-stations-profile-form>
                <admin-stations-frontend-form :form="$v.form" :is-shoutcast-installed="isShoutcastInstalled"
                                              :countries="countries"></admin-stations-frontend-form>

            </b-tabs>

            <slot name="submitButton">
                <b-card-body body-class="card-padding-sm">
                    <b-button size="lg" type="submit" variant="primary" :disabled="!isValid">
                        <translate key="lang_btn_save_changes">Save Changes</translate>
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
        countries: Object
    }
}


export default {
    name: 'AdminStationsForm',
    components: {AdminStationsFrontendForm, AdminStationsProfileForm},
    emits: ['error', 'submitted'],
    props: {
        createUrl: String,
        editUrl: String,
        isEditMode: Boolean
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
            }
        };

        if (this.showAdminTab) {
            let adminValidations = {
                form: {
                    media_storage_location_id: {},
                    recordings_storage_location_id: {},
                    podcasts_storage_location_id: {},
                    is_enabled: {},
                    radio_base_dir: {},

                }
            };

            formValidations = {...formValidations, ...adminValidations};
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
    computed: {
        isValid() {
            return !this.$v.form.$invalid;
        }
    },
    methods: {
        clear() {
            this.loading = false;
            this.error = null;

            let form = {
                name: '',
                description: '',
                genre: '',
                url: '',
                timezone: '',
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
            let form = {
                name: data.name,
                description: data.description,
                genre: data.genre,
                url: data.url,
                timezone: data.timezone,
                enable_public_page: data.enable_public_page,
                enable_on_demand: data.enable_on_demand,
                default_album_art_url: data.default_album_art_url,
                enable_on_demand_download: data.enable_on_demand_download,
                short_name: data.short_name,
                api_history_items: data.api_history_items,
                frontend_type: data.frontend_type,
                frontend_config: {
                    source_pw: data.frontend_config.source_pw,
                    admin_pw: data.frontend_config.admin_pw,
                    port: data.frontend_config.port,
                    max_listeners: data.frontend_config.max_listeners,
                    custom_config: data.frontend_config.custom_config,
                    banned_ips: data.frontend_config.banned_ips,
                    banned_countries: data.frontend_config.banned_countries,
                    allowed_ips: data.frontend_config.allowed_ips
                },
                backend_type: data.backend_type,
                backend_config: {
                    crossfade_type: data.backend_config.crossfade_type,
                    crossfade: data.backend_config.crossfade,
                    nrj: data.backend_config.nrj,
                    record_streams: data.backend_config.record_streams,
                    record_streams_format: data.backend_config.record_streams_format,
                    record_streams_bitrate: data.backend_config.record_streams_bitrate,
                    dj_port: data.backend_config.dj_port,
                    telnet_port: data.backend_config.telnet_port,
                    dj_buffer: data.backend_config.dj_buffer,
                    dj_mount_point: data.backend_config.dj_mount_point,
                    enable_replaygain_metadata: data.backend_config.enable_replaygain_metadata,
                    autodj_queue_length: data.backend_config.autodj_queue_length,
                    use_manual_autodj: data.backend_config.use_manual_autodj,
                    charset: data.backend_config.charset,
                    duplicate_prevention_time_range: data.backend_config.duplicate_prevention_time_range,
                },
                enable_requests: data.enable_requests,
                request_delay: data.request_delay,
                request_threshold: data.request_threshold,
                enable_streamers: data.enable_streamers,
                disconnect_deactivate_streamer: data.disconnect_deactivate_streamer,
            };

            if (this.showAdminTab) {
                let adminForm = {
                    media_storage_location_id: data.media_storage_location_id,
                    recordings_storage_location_id: data.recordings_storage_location_id,
                    podcasts_storage_location_id: data.podcasts_storage_location_id,
                    is_enabled: data.is_enabled,
                    radio_base_dir: data.radio_base_dir,
                };

                form = {...form, ...adminForm};
            }

            this.form = data;
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
