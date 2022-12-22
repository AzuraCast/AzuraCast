<template>
    <b-form-fieldset>
        <template #label>
            {{ $gettext('AzuraCast Update Checks') }}
        </template>

        <div class="form-row">
            <b-form-markup class="col-md-6" id="form_release_channel">
                <template #label>
                    {{ $gettext('Release Channel') }}
                </template>
                <template #description>
                    <a href="https://docs.azuracast.com/en/getting-started/updates/release-channels"
                       target="_blank">
                        {{ $gettext('Learn more about release channels in the AzuraCast docs.') }}
                    </a>
                </template>

                <p class="card-text font-weight-bold">
                    {{ langReleaseChannel }}
                </p>
            </b-form-markup>

            <b-wrapped-form-checkbox class="col-md-6" id="edit_form_check_for_updates"
                                     :field="form.check_for_updates">
                <template #label>
                    {{ $gettext('Show Update Announcements') }}
                </template>
                <template #description>
                    {{ $gettext('Show new releases within your update channel on the AzuraCast homepage.') }}
                </template>
            </b-wrapped-form-checkbox>
        </div>
    </b-form-fieldset>

    <b-form-fieldset>
        <template #label>
            {{ $gettext('LetsEncrypt') }}
        </template>
        <template #description>
            {{
                $gettext('LetsEncrypt provides simple, free SSL certificates allowing you to secure traffic through your control panel and radio streams.')
            }}
        </template>

        <div class="form-row">
            <b-wrapped-form-group class="col-md-6" id="edit_form_acme_domains"
                                  :field="form.acme_domains">
                <template #label>
                    {{ $gettext('Domain Name(s)') }}
                </template>
                <template #description>
                    {{
                        $gettext('All listed domain names should point to this AzuraCast installation. Separate multiple domain names with commas.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_acme_email"
                                  :field="form.acme_email" input-type="email">
                <template #label>
                    {{ $gettext('E-mail Address (Optional)') }}
                </template>
                <template #description>
                    {{ $gettext('Enter your e-mail address to receive updates about your certificate.') }}
                </template>
            </b-wrapped-form-group>

            <div class="form-group col">
                <b-button size="sm" variant="primary" :disabled="form.$anyDirty" @click="generateAcmeCert">
                    <icon icon="badge"></icon>
                    {{ $gettext('Generate/Renew Certificate') }}
                    <span v-if="form.$anyDirty">
                        ({{ $gettext('Save Changes first') }})
                    </span>
                </b-button>
            </div>
        </div>
    </b-form-fieldset>

    <b-form-fieldset>
        <template #label>
            {{ $gettext('E-mail Delivery Service') }}
        </template>
        <template #description>
            {{ $gettext('Used for "Forgot Password" functionality, web hooks and other functions.') }}
        </template>

        <div class="form-row">
            <b-wrapped-form-checkbox class="col-md-12" id="edit_form_mail_enabled"
                                     :field="form.mail_enabled">
                <template #label>
                    {{ $gettext('Enable Mail Delivery') }}
                </template>
            </b-wrapped-form-checkbox>
        </div>

        <div class="form-row mt-2" v-if="form.mail_enabled.$model">
            <b-wrapped-form-group class="col-md-6" id="edit_form_mail_sender_name"
                                  :field="form.mail_sender_name">
                <template #label>
                    {{ $gettext('Sender Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_mail_sender_email"
                                  :field="form.mail_sender_email" input-type="email">
                <template #label>
                    {{ $gettext('Sender E-mail Address') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-4" id="edit_form_mail_smtp_host"
                                  :field="form.mail_smtp_host">
                <template #label>
                    {{ $gettext('SMTP Host') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-3" id="edit_form_mail_smtp_port"
                                  :field="form.mail_smtp_port" input-type="number">
                <template #label>
                    {{ $gettext('SMTP Port') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-checkbox class="col-md-5" id="edit_form_mail_smtp_secure"
                                     :field="form.mail_smtp_secure">
                <template #label>
                    {{ $gettext('Use Secure (TLS) SMTP Connection') }}
                </template>
                <template #description>
                    {{ $gettext('Usually enabled for port 465, disabled for ports 587 or 25.') }}
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-group class="col-md-6" id="edit_form_mail_smtp_username"
                                  :field="form.mail_smtp_username">
                <template #label>
                    {{ $gettext('SMTP Username') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_mail_smtp_password"
                                  :field="form.mail_smtp_password" input-type="password">
                <template #label>
                    {{ $gettext('SMTP Password') }}
                </template>
            </b-wrapped-form-group>

            <div class="form-group col">
                <b-button size="sm" variant="primary" :disabled="form.$anyDirty" v-b-modal.send_test_message>
                    <icon icon="send"></icon>
                    {{ $gettext('Send Test Message') }}
                    <span v-if="form.$anyDirty">
                        ({{ $gettext('Save Changes first') }})
                    </span>
                </b-button>
            </div>
        </div>
    </b-form-fieldset>

    <b-form-fieldset>
        <template #label>
            {{ $gettext('Avatar Service') }}
        </template>

        <div class="form-row">
            <b-wrapped-form-group class="col-md-6" id="edit_form_avatar_service" :field="form.avatar_service">
                <template #label>
                    {{ $gettext('Avatar Service') }}
                </template>
                <template #default="props">
                    <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                        :options="avatarServiceOptions"></b-form-radio-group>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_avatar_default_url"
                                  :field="form.avatar_default_url">
                <template #label>
                    {{ $gettext('Default Avatar URL') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-fieldset>

    <b-form-fieldset>
        <template #label>
            {{ $gettext('Album Art') }}
        </template>

        <div class="form-row">
            <b-wrapped-form-checkbox class="col-md-6" id="use_external_album_art_in_apis"
                                     :field="form.use_external_album_art_in_apis">
                <template #label>
                    {{ $gettext('Check Web Services for Album Art for "Now Playing" Tracks') }}
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-checkbox class="col-md-6" id="use_external_album_art_when_processing_media"
                                     :field="form.use_external_album_art_when_processing_media">
                <template #label>
                    {{ $gettext('Check Web Services for Album Art When Uploading Media') }}
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-group class="col-md-12" id="edit_form_last_fm_api_key" :field="form.last_fm_api_key">
                <template #label>
                    {{ $gettext('Last.fm API Key') }}
                </template>
                <template #description>
                    {{ $gettext('This service can provide album art for tracks where none is available locally.') }}
                    <br>
                    <a href="https://www.last.fm/api/account/create" target="_blank">
                        {{ $gettext('Apply for an API key at Last.fm') }}
                    </a>
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-fieldset>

    <streaming-log-modal ref="acmeModal"></streaming-log-modal>

    <admin-settings-test-message-modal :test-message-url="testMessageUrl"></admin-settings-test-message-modal>
</template>

<script setup>
import BFormMarkup from "~/components/Form/BFormMarkup";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import AdminSettingsTestMessageModal from "~/components/Admin/Settings/TestMessageModal";
import Icon from "~/components/Common/Icon";
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import {computed, ref} from "vue";
import gettext from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    form: Object,
    releaseChannel: String,
    testMessageUrl: String,
    acmeUrl: String,
});

const {$gettext} = gettext;

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

const acmeModal = ref(); // BModal
const {wrapWithLoading} = useNotify();
const {axios} = useAxios();

const generateAcmeCert = () => {
    wrapWithLoading(
        axios.put(props.acmeUrl)
    ).then((resp) => {
        acmeModal.value.show(resp.data.links.log);
    });
}
</script>
