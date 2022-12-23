<template>
    <form class="form vue-form" @submit.prevent="submit">
        <slot name="preCard"></slot>

        <b-card no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <slot name="cardTitle">
                        {{ $gettext('System Settings') }}
                    </slot>
                </h2>
            </div>

            <slot name="cardUpper"></slot>

            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-overlay variant="card" :show="loading">
                <b-tabs pills card lazy>
                    <b-tab :title-link-class="getTabClass(v$.$validationGroups.generalTab)">
                        <template #title>
                            {{ $gettext('Settings') }}
                        </template>

                        <settings-general-tab :form="v$"></settings-general-tab>
                    </b-tab>

                    <b-tab :title-link-class="getTabClass(v$.$validationGroups.securityPrivacyTab)">
                        <template #title>
                            {{ $gettext('Security & Privacy') }}
                        </template>

                        <settings-security-privacy-tab :form="v$"></settings-security-privacy-tab>
                    </b-tab>

                    <b-tab :title-link-class="getTabClass(v$.$validationGroups.servicesTab)">
                        <template #title>
                            {{ $gettext('Services') }}
                        </template>

                        <settings-services-tab :form="v$"
                                               :release-channel="releaseChannel"
                                               :test-message-url="testMessageUrl"
                                               :acme-url="acmeUrl"></settings-services-tab>
                    </b-tab>
                </b-tabs>
            </b-overlay>

            <b-card-body body-class="card-padding-sm">
                <b-button size="lg" type="submit" :variant="(v$.$invalid) ? 'danger' : 'primary'">
                    <slot name="submitButtonName">
                        {{ $gettext('Save Changes') }}
                    </slot>
                </b-button>
            </b-card-body>
        </b-card>
    </form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import SettingsGeneralTab from "./Settings/GeneralTab";
import SettingsServicesTab from "./Settings/ServicesTab";
import SettingsSecurityPrivacyTab from "~/components/Admin/Settings/SecurityPrivacyTab";
import {onMounted, ref} from "vue";
import useVuelidate from "@vuelidate/core";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/vendor/bootstrapVue";
import gettext from "~/vendor/gettext";

const props = defineProps({
    apiUrl: String,
    testMessageUrl: String,
    acmeUrl: String,
    releaseChannel: {
        type: String,
        default: 'rolling',
        required: false
    }
});

const emits = defineEmits(['saved']);

const blankForm = {
    base_url: '',
    instance_name: '',
    prefer_browser_url: true,
    use_radio_proxy: true,
    history_keep_days: 7,
    enable_static_nowplaying: true,
    enable_advanced_features: true,
    analytics: null,
    always_use_ssl: false,
    api_access_control: '*',
    check_for_updates: 1,
    acme_email: '',
    acme_domains: '',
    mail_enabled: false,
    mail_sender_name: '',
    mail_sender_email: '',
    mail_smtp_host: '',
    mail_smtp_port: '',
    mail_smtp_secure: '',
    mail_smtp_username: '',
    mail_smtp_password: '',
    avatar_service: 'gravatar',
    avatar_default_url: '',
    use_external_album_art_in_apis: false,
    use_external_album_art_when_processing_media: false,
    last_fm_api_key: ''
};

const form = ref({...blankForm});

const validations = {
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
    last_fm_api_key: {},
    $validationGroups: {
        generalTab: [
            'base_url', 'instance_name', 'prefer_browser_url', 'use_radio_proxy',
            'history_keep_days', 'enable_static_nowplaying', 'enable_advanced_features'
        ],
        securityPrivacyTab: [
            'analytics', 'always_use_ssl', 'api_access_control'
        ],
        servicesTab: [
            'check_for_updates',
            'acme_email', 'acme_domains',
            'mail_enabled', 'mail_sender_name', 'mail_sender_email',
            'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_secure', 'mail_smtp_username',
            'mail_smtp_password', 'avatar_service', 'avatar_default_url',
            'use_external_album_art_in_apis', 'use_external_album_art_when_processing_media',
            'last_fm_api_key',
        ]
    }
};

const v$ = useVuelidate(validations, form);

const loading = ref(true);
const error = ref(null);

const getTabClass = (validationGroup) => {
    if (!loading.value && validationGroup.$invalid) {
        return 'text-danger';
    }
    return null;
};

const {axios} = useAxios();

const populateForm = (data) => {
    form.value = mergeExisting(form.value, data);
};

const relist = () => {
    v$.value.$reset();
    loading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        populateForm(resp.data);
        loading.value = false;
    });
};

onMounted(relist);

const {wrapWithLoading, notifySuccess} = useNotify();
const {$gettext} = gettext;

const submit = () => {
    v$.value.$touch();
    if (v$.value.$errors.length > 0) {
        return;
    }

    wrapWithLoading(
        axios({
            method: 'PUT',
            url: props.apiUrl,
            data: form.value
        })
    ).then(() => {
        emit('saved');

        notifySuccess($gettext('Changes saved.'));
        relist();
    });
}
</script>
