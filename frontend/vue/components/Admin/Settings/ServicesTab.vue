<template>
    <form-fieldset>
        <template #label>
            {{ $gettext('AzuraCast Update Checks') }}
        </template>

        <div class="row g-3">
            <form-markup
                id="form_release_channel"
                class="col-md-6"
            >
                <template #label>
                    {{ $gettext('Release Channel') }}
                </template>
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
                :field="form.check_for_updates"
            >
                <template #label>
                    {{ $gettext('Show Update Announcements') }}
                </template>
                <template #description>
                    {{ $gettext('Show new releases within your update channel on the AzuraCast homepage.') }}
                </template>
            </form-group-checkbox>
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
                :field="form.acme_domains"
            >
                <template #label>
                    {{ $gettext('Domain Name(s)') }}
                </template>
                <template #description>
                    {{
                        $gettext('All listed domain names should point to this AzuraCast installation. Separate multiple domain names with commas.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_acme_email"
                class="col-md-6"
                :field="form.acme_email"
                input-type="email"
            >
                <template #label>
                    {{ $gettext('E-mail Address (Optional)') }}
                </template>
                <template #description>
                    {{ $gettext('Enter your e-mail address to receive updates about your certificate.') }}
                </template>
            </form-group-field>

            <div class="form-group col">
                <button
                    class="btn btn-primary btn-sm"
                    :disabled="form.$anyDirty"
                    @click="generateAcmeCert"
                >
                    <icon icon="badge" />
                    <span>
                        {{ $gettext('Generate/Renew Certificate') }}
                        <span v-if="form.$anyDirty">
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
                :field="form.mail_enabled"
            >
                <template #label>
                    {{ $gettext('Enable Mail Delivery') }}
                </template>
            </form-group-checkbox>
        </div>

        <div
            v-if="form.mail_enabled.$model"
            class="row g-3 mt-2"
        >
            <form-group-field
                id="edit_form_mail_sender_name"
                class="col-md-6"
                :field="form.mail_sender_name"
            >
                <template #label>
                    {{ $gettext('Sender Name') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_mail_sender_email"
                class="col-md-6"
                :field="form.mail_sender_email"
                input-type="email"
            >
                <template #label>
                    {{ $gettext('Sender E-mail Address') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_mail_smtp_host"
                class="col-md-4"
                :field="form.mail_smtp_host"
            >
                <template #label>
                    {{ $gettext('SMTP Host') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_mail_smtp_port"
                class="col-md-3"
                :field="form.mail_smtp_port"
                input-type="number"
            >
                <template #label>
                    {{ $gettext('SMTP Port') }}
                </template>
            </form-group-field>

            <form-group-checkbox
                id="edit_form_mail_smtp_secure"
                class="col-md-5"
                :field="form.mail_smtp_secure"
            >
                <template #label>
                    {{ $gettext('Use Secure (TLS) SMTP Connection') }}
                </template>
                <template #description>
                    {{ $gettext('Usually enabled for port 465, disabled for ports 587 or 25.') }}
                </template>
            </form-group-checkbox>

            <form-group-field
                id="edit_form_mail_smtp_username"
                class="col-md-6"
                :field="form.mail_smtp_username"
            >
                <template #label>
                    {{ $gettext('SMTP Username') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_mail_smtp_password"
                class="col-md-6"
                :field="form.mail_smtp_password"
                input-type="password"
            >
                <template #label>
                    {{ $gettext('SMTP Password') }}
                </template>
            </form-group-field>

            <div class="form-group col">
                <button
                    class="btn btn-sm btn-primary"
                    :disabled="form.$anyDirty"
                    @click.prevent="openTestMessage"
                >
                    <icon icon="send" />
                    <span>
                        {{ $gettext('Send Test Message') }}
                        <span v-if="form.$anyDirty">
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
            <form-group-field
                id="edit_form_avatar_service"
                class="col-md-6"
                :field="form.avatar_service"
            >
                <template #label>
                    {{ $gettext('Avatar Service') }}
                </template>
                <template #default="slotProps">
                    <b-form-radio-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                        :options="avatarServiceOptions"
                    />
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_avatar_default_url"
                class="col-md-6"
                :field="form.avatar_default_url"
            >
                <template #label>
                    {{ $gettext('Default Avatar URL') }}
                </template>
            </form-group-field>
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
                :field="form.use_external_album_art_in_apis"
            >
                <template #label>
                    {{ $gettext('Check Web Services for Album Art for "Now Playing" Tracks') }}
                </template>
            </form-group-checkbox>

            <form-group-checkbox
                id="use_external_album_art_when_processing_media"
                class="col-md-6"
                :field="form.use_external_album_art_when_processing_media"
            >
                <template #label>
                    {{ $gettext('Check Web Services for Album Art When Uploading Media') }}
                </template>
            </form-group-checkbox>

            <form-group-field
                id="edit_form_last_fm_api_key"
                class="col-md-12"
                :field="form.last_fm_api_key"
            >
                <template #label>
                    {{ $gettext('Last.fm API Key') }}
                </template>
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
