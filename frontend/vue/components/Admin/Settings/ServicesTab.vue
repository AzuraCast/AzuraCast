<template>
    <b-tab :title="langTabTitle" :title-link-class="tabClass">
        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_update_checks">AzuraCast Update Checks</translate>
            </template>

            <b-row>
                <b-form-markup class="col-md-6" id="form_release_channel">
                    <template #label>
                        <translate key="lang_release_channel">Current Release Channel</translate>
                    </template>
                    <template #description>
                        <a href="https://docs.azuracast.com/en/getting-started/updates/release-channels"
                           target="_blank">
                            <translate key="lang_switch_release_channel">Learn more about release channels in the AzuraCast docs.</translate>
                        </a>
                    </template>

                    <p class="card-text font-weight-bold">
                        {{ langReleaseChannel }}
                    </p>
                </b-form-markup>

                <b-wrapped-form-group class="col-md-6" id="edit_form_check_for_updates"
                                      :field="form.check_for_updates">
                    <template #description>
                        <translate key="lang_edit_form_check_for_updates_desc">Show new releases within your update channel on the AzuraCast homepage.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_edit_form_check_for_updates">Show Update Announcements</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_email_delivery">E-mail Delivery Service</translate>
            </template>
            <template #description>
                <translate key="lang_section_email_delivery_desc">Used for "Forgot Password" functionality, web hooks and other functions.</translate>
            </template>

            <b-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_mail_enabled"
                                      :field="form.mail_enabled">
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_edit_form_mail_enabled">Enable Mail Delivery</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>

            <b-row v-if="form.mail_enabled.$model">
                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_sender_name"
                                      :field="form.mail_sender_name">
                    <template #label>
                        <translate key="lang_edit_form_mail_sender_name">Sender Name</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_sender_email"
                                      :field="form.mail_sender_email" input-type="email">
                    <template #label>
                        <translate key="lang_edit_form_mail_sender_email">Sender E-mail Address</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-4" id="edit_form_mail_smtp_host"
                                      :field="form.mail_smtp_host">
                    <template #label>
                        <translate key="lang_edit_form_mail_smtp_host">SMTP Host</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-3" id="edit_form_mail_smtp_port"
                                      :field="form.mail_smtp_port" input-type="number">
                    <template #label>
                        <translate key="lang_edit_form_mail_smtp_port">SMTP Port</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-5" id="edit_form_mail_smtp_secure"
                                      :field="form.mail_smtp_secure">
                    <template #description>
                        <translate key="lang_edit_form_mail_smtp_secure_desc">Usually enabled for port 465, disabled for ports 587 or 25.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_mail_smtp_secure">Use Secure (TLS) SMTP Connection</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_smtp_username"
                                      :field="form.mail_smtp_username">
                    <template #label>
                        <translate key="lang_edit_form_mail_smtp_username">SMTP Username</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mail_smtp_password"
                                      :field="form.mail_smtp_password" input-type="password">
                    <template #label>
                        <translate key="lang_edit_form_mail_smtp_password">SMTP Password</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_avatar_services">Avatar Services</translate>
            </template>

            <b-row>
                <b-wrapped-form-group class="col-md-6" id="edit_form_avatar_service" :field="form.avatar_service">
                    <template #label>
                        <translate key="lang_edit_form_avatar_service">Avatar Service</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                            :options="avatarServiceOptions"></b-form-radio-group>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_avatar_default_url"
                                      :field="form.avatar_default_url">
                    <template #label>
                        <translate key="lang_edit_form_avatar_default_url">Default Avatar URL</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_section_album_art_services">Album Art Services</translate>
            </template>

            <b-row>
                <b-wrapped-form-group class="col-md-6" id="use_external_album_art_in_apis"
                                      :field="form.use_external_album_art_in_apis">
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_use_external_album_art_in_apis">Check Web Services for Album Art for "Now Playing" Tracks</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="use_external_album_art_when_processing_media"
                                      :field="form.use_external_album_art_when_processing_media">
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_use_external_album_art_when_processing_media">Check Web Services for Album Art When Uploading Media</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_last_fm_api_key" :field="form.last_fm_api_key">
                    <template #label>
                        <translate key="lang_edit_form_last_fm_api_key">Last.fm API Key</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_last_fm_api_key_desc">This service can provide album art for tracks where none is available locally.</translate>
                        <br>
                        <a href="https://www.last.fm/api/account/create" target="_blank">
                            <translate key="lang_edit_form_last_fm_url">Apply for an API key at Last.fm</translate>
                        </a>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-fieldset>
    </b-tab>
</template>

<script>
import BFormMarkup from "~/components/Form/BFormMarkup";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";

export default {
    name: 'SettingsServicesTab',
    components: {BFormFieldset, BWrappedFormGroup, BFormMarkup},
    props: {
        form: Object,
        tabClass: {},
        releaseChannel: String,
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Services');
        },
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
    }
}
</script>
