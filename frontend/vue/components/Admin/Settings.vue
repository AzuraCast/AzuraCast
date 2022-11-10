<template>
    <form class="form vue-form" @submit.prevent="submit">
        <slot name="preCard"></slot>

        <b-card no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <slot name="cardTitle">
                        <translate key="lang_settings">System Settings</translate>
                    </slot>
                </h2>
            </div>

            <slot name="cardUpper"></slot>

            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-overlay variant="card" :show="loading">
                <b-tabs card lazy justified>
                    <b-tab :title-link-class="getTabClass($v.generalTab)">
                        <template #title>
                            <translate key="tab_general">Settings</translate>
                        </template>

                        <settings-general-tab :form="$v.form"></settings-general-tab>
                    </b-tab>

                    <b-tab :title-link-class="getTabClass($v.securityPrivacyTab)">
                        <template #title>
                            <translate key="tab_security_privacy">Security & Privacy</translate>
                        </template>

                        <settings-security-privacy-tab :form="$v.form"></settings-security-privacy-tab>
                    </b-tab>

                    <b-tab :title-link-class="getTabClass($v.servicesTab)">
                        <template #title>
                            <translate key="tab_services">Services</translate>
                        </template>

                        <settings-services-tab :form="$v.form"
                                               :release-channel="releaseChannel"
                                               :test-message-url="testMessageUrl"
                                               :acme-url="acmeUrl"></settings-services-tab>
                    </b-tab>
                </b-tabs>
            </b-overlay>

            <b-card-body body-class="card-padding-sm">
                <b-button size="lg" type="submit" :variant="($v.form.$invalid) ? 'danger' : 'primary'">
                    <slot name="submitButtonName">
                        <translate key="lang_btn_save_changes">Save Changes</translate>
                    </slot>
                </b-button>
            </b-card-body>
        </b-card>
    </form>
</template>

<script>
import SettingsGeneralTab from "./Settings/GeneralTab";
import SettingsServicesTab from "./Settings/ServicesTab";
import {validationMixin} from "vuelidate";
import {required} from 'vuelidate/dist/validators.min.js';
import SettingsSecurityPrivacyTab from "~/components/Admin/Settings/SecurityPrivacyTab";

export default {
    name: 'AdminSettings',
    components: {SettingsSecurityPrivacyTab, SettingsServicesTab, SettingsGeneralTab},
    emits: ['saved'],
    props: {
        apiUrl: String,
        testMessageUrl: String,
        acmeUrl: String,
        releaseChannel: {
            type: String,
            default: 'rolling',
            required: false
        }
    },
    mixins: [
        validationMixin
    ],
    data() {
        return {
            loading: true,
            error: null,
            form: {},
        };
    },
    validations: {
        form: {
            base_url: {required},
            instance_name: {},
            prefer_browser_url: {},
            use_radio_proxy: {},
            history_keep_days: {required},
            enable_static_nowplaying: {},
            enable_advanced_features: {},

            analytics: {required},

            always_use_ssl: {},
            api_access_control: {},

            check_for_updates: {},
            acme_email: {},
            acme_domains: {},
            mail_enabled: {},
            mail_sender_name: {},
            mail_sender_email: {},
            mail_smtp_host: {},
            mail_smtp_port: {},
            mail_smtp_secure: {},
            mail_smtp_username: {},
            mail_smtp_password: {},
            avatar_service: {},
            avatar_default_url: {},
            use_external_album_art_in_apis: {},
            use_external_album_art_when_processing_media: {},
            last_fm_api_key: {}
        },
        generalTab: [
            'form.base_url', 'form.instance_name', 'form.prefer_browser_url', 'form.use_radio_proxy',
            'form.history_keep_days', 'form.enable_static_nowplaying', 'form.enable_advanced_features'
        ],
        securityPrivacyTab: [
            'form.analytics', 'form.always_use_ssl', 'form.api_access_control'
        ],
        servicesTab: [
            'form.check_for_updates',
            'form.acme_email', 'form.acme_domains',
            'form.mail_enabled', 'form.mail_sender_name', 'form.mail_sender_email',
            'form.mail_smtp_host', 'form.mail_smtp_port', 'form.mail_smtp_secure', 'form.mail_smtp_username',
            'form.mail_smtp_password', 'form.avatar_service', 'form.avatar_default_url',
            'form.use_external_album_art_in_apis', 'form.use_external_album_art_when_processing_media',
            'form.last_fm_api_key',
        ]
    },
    mounted() {
        this.relist();
    },
    methods: {
        getTabClass(validationGroup) {
            if (!this.loading && validationGroup.$invalid) {
                return 'text-danger';
            }
            return null;
        },
        relist() {
            this.$v.form.$reset();
            this.loading = true;

            this.axios.get(this.apiUrl).then((resp) => {
                this.populateForm(resp.data);
                this.loading = false;
            });
        },
        populateForm(data) {
            this.form = {
                base_url: data.base_url,
                instance_name: data.instance_name,
                prefer_browser_url: data.prefer_browser_url,
                use_radio_proxy: data.use_radio_proxy,
                history_keep_days: data.history_keep_days,
                enable_static_nowplaying: data.enable_static_nowplaying,
                enable_advanced_features: data.enable_advanced_features,

                analytics: data.analytics,

                always_use_ssl: data.always_use_ssl,
                api_access_control: data.api_access_control,

                check_for_updates: data.check_for_updates,
                acme_email: data.acme_email,
                acme_domains: data.acme_domains,
                mail_enabled: data.mail_enabled,
                mail_sender_name: data.mail_sender_name,
                mail_sender_email: data.mail_sender_email,
                mail_smtp_host: data.mail_smtp_host,
                mail_smtp_port: data.mail_smtp_port,
                mail_smtp_secure: data.mail_smtp_secure,
                mail_smtp_username: data.mail_smtp_username,
                mail_smtp_password: data.mail_smtp_password,
                avatar_service: data.avatar_service,
                avatar_default_url: data.avatar_default_url,
                use_external_album_art_in_apis: data.use_external_album_art_in_apis,
                use_external_album_art_when_processing_media: data.use_external_album_art_when_processing_media,
                last_fm_api_key: data.last_fm_api_key
            }
        },
        submit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.$wrapWithLoading(
                this.axios({
                    method: 'PUT',
                    url: this.apiUrl,
                    data: this.form
                })
            ).then((resp) => {
                this.$emit('saved');

                this.$notifySuccess(this.$gettext('Changes saved.'));
                this.relist();
            });
        }
    }
}
</script>
