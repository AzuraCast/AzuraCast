<template>
    <div>
        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_update_checks">AzuraCast Update Checks</translate>
            </template>

            <b-form-row>
                <b-form-markup class="col-md-6" id="form_release_channel">
                    <template #label="{lang}">
                        <translate :key="lang">Release Channel</translate>
                    </template>
                    <template #description="{lang}">
                        <a href="https://docs.azuracast.com/en/getting-started/updates/release-channels"
                           target="_blank">
                            <translate :key="lang">Learn more about release channels in the AzuraCast docs.</translate>
                        </a>
                    </template>

                    <p class="card-text font-weight-bold">
                        {{ langReleaseChannel }}
                    </p>
                </b-form-markup>

                <b-wrapped-form-checkbox class="col-md-6" id="edit_form_check_for_updates"
                                         :field="form.check_for_updates">
                    <template #label="{lang}">
                        <translate :key="lang">Show Update Announcements</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">Show new releases within your update channel on the AzuraCast homepage.</translate>
                    </template>
                </b-wrapped-form-checkbox>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_letsencrypt">LetsEncrypt</translate>
            </template>
            <template #description>
                <translate key="lang_section_letsencrypt_desc">LetsEncrypt provides simple, free SSL certificates allowing you to secure traffic through your control panel and radio streams.</translate>
            </template>

            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="edit_form_acme_domains"
                                      :field="form.acme_domains">
                    <template #label="{lang}">
                        <translate :key="lang">Domain Name(s)</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">All listed domain names should point to this AzuraCast installation. Separate multiple domain names with commas.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_acme_email"
                                      :field="form.acme_email" input-type="email">
                    <template #label="{lang}">
                        <translate :key="lang">E-mail Address (Optional)</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">Enter your e-mail address to receive updates about your certificate.</translate>
                    </template>
                </b-wrapped-form-group>

                <div class="form-group col">
                    <b-button size="sm" variant="primary" :disabled="form.$anyDirty" @click="generateAcmeCert">
                        <icon icon="badge"></icon>
                        <translate key="lang_btn_acme_cert">Generate/Renew Certificate</translate>
                        <span v-if="form.$anyDirty">
                        (<translate key="lang_btn_acme_cert_save_changes">Save Changes first</translate>)
                    </span>
                    </b-button>
                </div>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_email_delivery">E-mail Delivery Service</translate>
            </template>
            <template #description>
                <translate key="lang_section_email_delivery_desc">Used for "Forgot Password" functionality, web hooks and other functions.</translate>
            </template>

            <b-form-row>
                <b-wrapped-form-checkbox class="col-md-12" id="edit_form_mail_enabled"
                                         :field="form.mail_enabled">
                    <template #label="{lang}">
                        <translate :key="lang">Enable Mail Delivery</translate>
                    </template>
                </b-wrapped-form-checkbox>
            </b-form-row>

            <b-form-row v-if="form.mail_enabled.$model" class="mt-2">
                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_sender_name"
                                      :field="form.mail_sender_name">
                    <template #label="{lang}">
                        <translate :key="lang">Sender Name</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_sender_email"
                                      :field="form.mail_sender_email" input-type="email">
                    <template #label="{lang}">
                        <translate :key="lang">Sender E-mail Address</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-4" id="edit_form_mail_smtp_host"
                                      :field="form.mail_smtp_host">
                    <template #label="{lang}">
                        <translate :key="lang">SMTP Host</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-3" id="edit_form_mail_smtp_port"
                                      :field="form.mail_smtp_port" input-type="number">
                    <template #label="{lang}">
                        <translate :key="lang">SMTP Port</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox class="col-md-5" id="edit_form_mail_smtp_secure"
                                         :field="form.mail_smtp_secure">
                    <template #label="{lang}">
                        <translate :key="lang">Use Secure (TLS) SMTP Connection</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Usually enabled for port 465, disabled for ports 587 or 25.</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_smtp_username"
                                      :field="form.mail_smtp_username">
                    <template #label="{lang}">
                        <translate :key="lang">SMTP Username</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_smtp_password"
                                      :field="form.mail_smtp_password" input-type="password">
                    <template #label="{lang}">
                        <translate :key="lang">SMTP Password</translate>
                    </template>
                </b-wrapped-form-group>

                <div class="form-group col">
                    <b-button size="sm" variant="primary" :disabled="form.$anyDirty" v-b-modal.send_test_message>
                        <icon icon="send"></icon>
                        <translate key="lang_btn_test_message">Send Test Message</translate>
                        <span v-if="form.$anyDirty">
                            (<translate key="lang_btn_test_message_save_changes">Save Changes first</translate>)
                        </span>
                    </b-button>
                </div>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_avatar_services">Avatar Service</translate>
            </template>

            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="edit_form_avatar_service" :field="form.avatar_service">
                    <template #label="{lang}">
                        <translate :key="lang">Avatar Service</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                            :options="avatarServiceOptions"></b-form-radio-group>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_avatar_default_url"
                                      :field="form.avatar_default_url">
                    <template #label="{lang}">
                        <translate :key="lang">Default Avatar URL</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_album_art_services">Album Art</translate>
            </template>

            <b-form-row>
                <b-wrapped-form-checkbox class="col-md-6" id="use_external_album_art_in_apis"
                                         :field="form.use_external_album_art_in_apis">
                    <template #label="{lang}">
                        <translate :key="lang">Check Web Services for Album Art for "Now Playing" Tracks</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox class="col-md-6" id="use_external_album_art_when_processing_media"
                                         :field="form.use_external_album_art_when_processing_media">
                    <template #label="{lang}">
                        <translate :key="lang">Check Web Services for Album Art When Uploading Media</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-12" id="edit_form_last_fm_api_key" :field="form.last_fm_api_key">
                    <template #label="{lang}">
                        <translate :key="lang">Last.fm API Key</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">This service can provide album art for tracks where none is available locally.</translate>
                        <br>
                        <a href="https://www.last.fm/api/account/create" target="_blank">
                            <translate :key="lang+'2'">Apply for an API key at Last.fm</translate>
                        </a>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>

        <streaming-log-modal ref="acmeModal"></streaming-log-modal>

        <admin-settings-test-message-modal :test-message-url="testMessageUrl"></admin-settings-test-message-modal>
    </div>
</template>

<script>
import BFormMarkup from "~/components/Form/BFormMarkup";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import AdminSettingsTestMessageModal from "~/components/Admin/Settings/TestMessageModal";
import Icon from "~/components/Common/Icon";
import StreamingLogModal from "~/components/Common/StreamingLogModal";

export default {
    name: 'SettingsServicesTab',
    components: {
        StreamingLogModal,
        Icon,
        AdminSettingsTestMessageModal,
        BWrappedFormCheckbox,
        BFormFieldset,
        BWrappedFormGroup,
        BFormMarkup
    },
    props: {
        form: Object,
        releaseChannel: String,
        testMessageUrl: String,
        acmeUrl: String,
    },
    computed: {
        langReleaseChannel() {
            return (this.releaseChannel === 'stable')
                ? this.$gettext('Stable')
                : this.$gettext('Rolling Release');
        },
        avatarServiceOptions() {
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
                    text: this.$gettext('Disabled')
                }
            ]
        },
    },
    methods: {
        generateAcmeCert() {
            this.$wrapWithLoading(
                this.axios.put(this.acmeUrl)
            ).then((resp) => {
                this.$refs.acmeModal.show(resp.data.links.log);
            });
        }
    }
}
</script>
