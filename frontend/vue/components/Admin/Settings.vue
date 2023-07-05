<template>
    <form
        class="form vue-form"
        @submit.prevent="submit"
    >
        <slot name="preCard" />

        <section
            class="card"
            role="region"
            aria-labelledby="hdr_system_settings"
        >
            <div class="card-header text-bg-primary">
                <h2
                    id="hdr_system_settings"
                    class="card-title"
                >
                    <slot name="cardTitle">
                        {{ $gettext('System Settings') }}
                    </slot>
                </h2>
            </div>

            <slot name="cardUpper" />

            <div
                v-show="error != null"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <div class="card-body">
                <loading :loading="isLoading">
                    <o-tabs
                        nav-tabs-class="nav-tabs"
                        content-class="mt-3"
                    >
                        <o-tab-item
                            :label="$gettext('Settings')"
                            :item-header-class="getTabClass(v$.$validationGroups.generalTab)"
                        >
                            <settings-general-tab :form="v$" />
                        </o-tab-item>
                        <o-tab-item
                            :label="$gettext('Security & Privacy')"
                            :item-header-class="getTabClass(v$.$validationGroups.securityPrivacyTab)"
                        >
                            <settings-security-privacy-tab :form="v$" />
                        </o-tab-item>
                        <o-tab-item
                            :label="$gettext('Services')"
                            :item-header-class="getTabClass(v$.$validationGroups.servicesTab)"
                        >
                            <settings-services-tab
                                :form="v$"
                                :release-channel="releaseChannel"
                                :test-message-url="testMessageUrl"
                                :acme-url="acmeUrl"
                            />
                        </o-tab-item>
                    </o-tabs>
                </loading>
            </div>

            <div class="card-body">
                <button
                    type="submit"
                    class="btn btn-lg"
                    :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                >
                    <slot name="submitButtonName">
                        {{ $gettext('Save Changes') }}
                    </slot>
                </button>
            </div>
        </section>
    </form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import SettingsGeneralTab from "./Settings/GeneralTab.vue";
import SettingsServicesTab from "./Settings/ServicesTab.vue";
import SettingsSecurityPrivacyTab from "~/components/Admin/Settings/SecurityPrivacyTab.vue";
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import Loading from "~/components/Common/Loading.vue";

const props = defineProps({
    apiUrl: {
        type: String,
        required: true,
    },
    testMessageUrl: {
        type: String,
        required: true
    },
    acmeUrl: {
        type: String,
        required: true
    },
    releaseChannel: {
        type: String,
        default: 'rolling',
        required: false
    }
});

const emit = defineEmits(['saved']);

const {form, v$, ifValid} = useVuelidateOnForm(
    {
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
        ip_source: {},

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
                'analytics', 'always_use_ssl', 'ip_source', 'api_access_control'
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
    },
    {
        base_url: '',
        instance_name: '',
        prefer_browser_url: true,
        use_radio_proxy: true,
        history_keep_days: 7,
        enable_static_nowplaying: true,
        enable_advanced_features: true,
        analytics: null,
        always_use_ssl: false,
        ip_source: 'local',
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
    }
);

const isLoading = ref(true);
const error = ref(null);

const getTabClass = (validationGroup) => {
    if (!isLoading.value && validationGroup.$invalid) {
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
    isLoading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        populateForm(resp.data);
        isLoading.value = false;
    });
};

onMounted(relist);

const {wrapWithLoading, notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const submit = () => {
    ifValid(() => {
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
    });
}
</script>
