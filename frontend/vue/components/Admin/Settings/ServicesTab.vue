<template>
    <tab
        :label="$gettext('Services')"
        :item-header-class="tabClass"
    >
        <form-fieldset>
            <template #label>
                {{ $gettext('AzuraCast Update Checks') }}
            </template>

            <div class="row g-3">
                <form-markup
                    id="form_release_channel"
                    class="col-md-6"
                    :label="$gettext('Release Channel')"
                >
                    <template #description>
                        <a
                            href="https://docs.azuracast.com/en/getting-started/updates/release-channels"
                            target="_blank"
                        >
                            {{ $gettext('Learn more about release channels in the AzuraCast docs.') }}
                        </a>
                    </template>

                    <p class="card-text font-weight-bold">
                        {{ langReleaseChannel }}
                    </p>
                </form-markup>

                <form-group-checkbox
                    id="edit_form_check_for_updates"
                    class="col-md-6"
                    :field="v$.check_for_updates"
                    :label="$gettext('Show Update Announcements')"
                    :description="$gettext('Show new releases within your update channel on the AzuraCast homepage.')"
                />
            </div>
        </form-fieldset>

        <form-fieldset>
            <template #label>
                {{ $gettext('LetsEncrypt') }}
            </template>
            <template #description>
                {{
                    $gettext('LetsEncrypt provides simple, free SSL certificates allowing you to secure traffic through your control panel and radio streams.')
                }}
            </template>

            <div class="row g-3">
                <form-group-field
                    id="edit_form_acme_domains"
                    class="col-md-6"
                    :field="v$.acme_domains"
                    :label="$gettext('Domain Name(s)')"
                    :description="$gettext('All listed domain names should point to this AzuraCast installation. Separate multiple domain names with commas.')"
                />

                <form-group-field
                    id="edit_form_acme_email"
                    class="col-md-6"
                    :field="v$.acme_email"
                    input-type="email"
                    :label="$gettext('E-mail Address (Optional)')"
                    :description="$gettext('Enter your e-mail address to receive updates about your certificate.')"
                />

                <div class="form-group col">
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        :disabled="v$.$anyDirty"
                        @click="generateAcmeCert"
                    >
                        <icon icon="badge" />
                        <span>
                            {{ $gettext('Generate/Renew Certificate') }}
                            <span v-if="v$.$anyDirty">
                                ({{ $gettext('Save Changes first') }})
                            </span>
                        </span>
                    </button>
                </div>
            </div>
        </form-fieldset>

        <form-fieldset>
            <template #label>
                {{ $gettext('E-mail Delivery Service') }}
            </template>
            <template #description>
                {{ $gettext('Used for "Forgot Password" functionality, web hooks and other functions.') }}
            </template>

            <div class="row g-3">
                <form-group-checkbox
                    id="edit_form_mail_enabled"
                    class="col-md-12"
                    :field="v$.mail_enabled"
                    :label="$gettext('Enable Mail Delivery')"
                />
            </div>

            <div
                v-if="form.mail_enabled"
                class="row g-3 mt-2"
            >
                <form-group-field
                    id="edit_form_mail_sender_name"
                    class="col-md-6"
                    :field="v$.mail_sender_name"
                    :label="$gettext('Sender Name')"
                />

                <form-group-field
                    id="edit_form_mail_sender_email"
                    class="col-md-6"
                    :field="v$.mail_sender_email"
                    input-type="email"
                    :label="$gettext('Sender E-mail Address')"
                />

                <form-group-field
                    id="edit_form_mail_smtp_host"
                    class="col-md-4"
                    :field="v$.mail_smtp_host"
                    :label="$gettext('SMTP Host')"
                />

                <form-group-field
                    id="edit_form_mail_smtp_port"
                    class="col-md-3"
                    :field="v$.mail_smtp_port"
                    input-type="number"
                    :label="$gettext('SMTP Port')"
                />

                <form-group-checkbox
                    id="edit_form_mail_smtp_secure"
                    class="col-md-5"
                    :field="v$.mail_smtp_secure"
                    :label="$gettext('Use Secure (TLS) SMTP Connection')"
                    :description="$gettext('Usually enabled for port 465, disabled for ports 587 or 25.')"
                />

                <form-group-field
                    id="edit_form_mail_smtp_username"
                    class="col-md-6"
                    :field="v$.mail_smtp_username"
                    :label="$gettext('SMTP Username')"
                />

                <form-group-field
                    id="edit_form_mail_smtp_password"
                    class="col-md-6"
                    :field="v$.mail_smtp_password"
                    input-type="password"
                    :label="$gettext('SMTP Password')"
                />

                <div class="form-group col">
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        :disabled="v$.$anyDirty"
                        @click="openTestMessage"
                    >
                        <icon icon="send" />
                        <span>
                            {{ $gettext('Send Test Message') }}
                            <span v-if="v$.$anyDirty">
                                ({{ $gettext('Save Changes first') }})
                            </span>
                        </span>
                    </button>
                </div>
            </div>
        </form-fieldset>

        <form-fieldset>
            <template #label>
                {{ $gettext('Avatar Service') }}
            </template>

            <div class="row g-3">
                <form-group-multi-check
                    id="edit_form_avatar_service"
                    class="col-md-6"
                    :field="v$.avatar_service"
                    :options="avatarServiceOptions"
                    stacked
                    radio
                    :label="$gettext('Avatar Service')"
                />

                <form-group-field
                    id="edit_form_avatar_default_url"
                    class="col-md-6"
                    :field="v$.avatar_default_url"
                    :label="$gettext('Default Avatar URL')"
                />
            </div>
        </form-fieldset>

        <form-fieldset>
            <template #label>
                {{ $gettext('Album Art') }}
            </template>

            <div class="row g-3">
                <form-group-checkbox
                    id="use_external_album_art_in_apis"
                    class="col-md-6"
                    :field="v$.use_external_album_art_in_apis"
                >
                    <template #label>
                        {{ $gettext('Check Web Services for Album Art for "Now Playing" Tracks') }}
                    </template>
                </form-group-checkbox>

                <form-group-checkbox
                    id="use_external_album_art_when_processing_media"
                    class="col-md-6"
                    :field="v$.use_external_album_art_when_processing_media"
                    :label="$gettext('Check Web Services for Album Art When Uploading Media')"
                />

                <form-group-field
                    id="edit_form_last_fm_api_key"
                    class="col-md-12"
                    :field="v$.last_fm_api_key"
                    :label="$gettext('Last.fm API Key')"
                >
                    <template #description>
                        {{ $gettext('This service can provide album art for tracks where none is available locally.') }}
                        <br>
                        <a
                            href="https://www.last.fm/api/account/create"
                            target="_blank"
                        >
                            {{ $gettext('Apply for an API key at Last.fm') }}
                        </a>
                    </template>
                </form-group-field>
            </div>
        </form-fieldset>
    </tab>

    <streaming-log-modal ref="$acmeModal" />

    <admin-settings-test-message-modal
        ref="$testMessageModal"
        :test-message-url="testMessageUrl"
    />
</template>

<script setup>
import FormMarkup from "~/components/Form/FormMarkup.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormFieldset from "~/components/Form/FormFieldset";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import AdminSettingsTestMessageModal from "~/components/Admin/Settings/TestMessageModal.vue";
import Icon from "~/components/Common/Icon.vue";
import StreamingLogModal from "~/components/Common/StreamingLogModal.vue";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    releaseChannel: {
        type: String,
        required: true
    },
    testMessageUrl: {
        type: String,
        required: true
    },
    acmeUrl: {
        type: String,
        required: true
    },
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
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
    },
    form,
    {
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

const {$gettext} = useTranslate();

const langReleaseChannel = computed(() => {
    return (props.releaseChannel === 'stable')
        ? $gettext('Stable')
        : $gettext('Rolling Release');
});

const avatarServiceOptions = computed(() => {
    return [
        {
            value: 'libravatar',
            text: 'Libravatar'
        },
        {
            value: 'gravatar',
            text: 'Gravatar'
        },
        {
            value: 'disabled',
            text: $gettext('Disabled')
        }
    ]
});

const $acmeModal = ref(); // StreamingLogModal
const {wrapWithLoading} = useNotify();
const {axios} = useAxios();

const generateAcmeCert = () => {
    wrapWithLoading(
        axios.put(props.acmeUrl)
    ).then((resp) => {
        $acmeModal.value.show(resp.data.links.log);
    });
}

const $testMessageModal = ref(); // TestMessageModal

const openTestMessage = () => {
    $testMessageModal.value.open();
}
</script>
