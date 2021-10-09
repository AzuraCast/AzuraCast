<template>
    <form @submit.prevent="submit">
        <b-card no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_settings">System Settings</translate>
                </h2>
            </div>

            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-overlay variant="card" :show="loading">
                <b-tabs card lazy>
                    <settings-general-tab :form="$v.form"></settings-general-tab>
                    <settings-privacy-tab :form="$v.form"></settings-privacy-tab>
                    <settings-security-tab :form="$v.form"></settings-security-tab>
                    <settings-services-tab :form="$v.form"></settings-services-tab>
                </b-tabs>
            </b-overlay>

            <b-card-body body-class="card-padding-sm">
                <b-button size="lg" type="submit" variant="primary">
                    <translate key="lang_btn_save_changes">Save Changes</translate>
                </b-button>
            </b-card-body>
        </b-card>
    </form>
</template>

<script>
import SettingsGeneralTab from "./Settings/GeneralTab";
import SettingsPrivacyTab from "./Settings/PrivacyTab";
import SettingsSecurityTab from "./Settings/ServicesTab";
import SettingsServicesTab from "./Settings/ServicesTab";
import {validationMixin} from "vuelidate";
import {required} from 'vuelidate/dist/validators.min.js';

export default {
    name: 'AdminSettings',
    components: {SettingsServicesTab, SettingsSecurityTab, SettingsPrivacyTab, SettingsGeneralTab},
    props: {
        apiUrl: String,
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
            enable_websockets: {},
            enable_advanced_features: {},

            analytics: {required},

            always_use_ssl: {},
            api_access_control: {},

            check_for_updates: {},
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
            'form.history_keep_days', 'form.enable_websockets', 'form.enable_advanced_features'
        ],
        privacyTab: [
            'form.analytics',
        ],
        securityTab: [
            'form.always_use_ssl', 'form.api_access_control'
        ],
        servicesTab: [
            'form.check_for_updates', 'form.mail_enabled', 'form.mail_sender_name', 'form.mail_sender_email',
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
        relist() {
            this.$v.form.$reset();
            this.loading = true;

            this.axios.get(this.apiUrl).then((resp) => {
                this.populateForm(resp.data);
                this.loading = false;
            }).catch((error) => {
                this.close();
            });
        },
        populateForm(data) {
            this.form = {
                'public_theme': data.public_theme,
                'hide_album_art': data.hide_album_art,
                'homepage_redirect_url': data.homepage_redirect_url,
                'default_album_art_url': data.default_album_art_url,
                'hide_product_name': data.hide_product_name,
                'public_custom_css': data.public_custom_css,
                'public_custom_js': data.public_custom_js,
                'internal_custom_css': data.internal_custom_css
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
                this.$notifySuccess(this.$gettext('Changes saved.'));
                this.relist();
            });

        }
    }
}
</script>
