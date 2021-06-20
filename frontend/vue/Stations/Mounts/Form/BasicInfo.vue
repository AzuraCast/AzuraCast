<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>

            <b-row class="mb-3">
                <b-form-group class="col-md-6" label-for="edit_form_name">
                    <template #label>
                        <translate key="lang_edit_form_name">Mount Point URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_name_desc">This name should always begin with a slash (/), and must be a valid URL, such as /autodj.mp3</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_name" v-model="form.name.$model"
                                  :state="form.name.$dirty ? !form.name.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_display_name">
                    <template #label>
                        <translate key="lang_edit_form_display_name">Display Name</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_display_name_desc">The display name assigned to this mount point when viewing it on administrative or public pages. Leave blank to automatically generate one.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_display_name" v-model="form.display_name.$model"
                                  :state="form.display_name.$dirty ? !form.display_name.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_is_visible_on_public_pages">
                    <template #description>
                        <translate key="lang_edit_form_is_visible_on_public_pages_desc">Enable to allow listeners to select this mount point on this station's public pages.</translate>
                    </template>
                    <b-form-checkbox id="edit_form_is_visible_on_public_pages" v-model="form.is_visible_on_public_pages.$model">
                        <translate key="lang_edit_form_is_visible_on_public_pages">Show on Public Pages</translate>
                    </b-form-checkbox>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_is_default">
                    <template #description>
                        <translate key="lang_edit_form_is_default_desc">If this mount is the default, it will be played on the radio preview and the public radio page in this system.</translate>
                    </template>
                    <b-form-checkbox id="edit_form_is_default" v-model="form.is_default.$model">
                        <translate key="lang_edit_form_is_default">Set as Default Mount Point</translate>
                    </b-form-checkbox>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_relay_url">
                    <template #label>
                        <translate key="lang_edit_form_relay_url">Relay Stream URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_relay_url_desc">Enter the full URL of another stream to relay its broadcast through this mount point.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_relay_url" v-model="form.relay_url.$model"
                                  :state="form.relay_url.$dirty ? !form.relay_url.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_is_public">
                    <template #description>
                        <translate key="lang_edit_form_is_public_desc">Enable to advertise this mount point on "Yellow Pages" public radio directories.</translate>
                    </template>
                    <b-form-checkbox id="edit_form_is_public" v-model="form.is_public.$model">
                        <translate key="lang_edit_form_is_public">Publish to "Yellow Pages" Directories</translate>
                    </b-form-checkbox>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_max_listener_duration">
                    <template #label>
                        <translate key="lang_edit_form_max_listener_duration">Max Listener Duration</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_max_listener_duration_desc">Set the length of time (seconds) a listener will stay connected to the stream. If set to 0, listeners can stay connected infinitely.</translate>
                    </template>
                    <b-form-input type="number" min="0" max="2147483647" id="edit_form_max_listener_duration" v-model="form.max_listener_duration.$model"
                                  :state="form.max_listener_duration.$dirty ? !form.max_listener_duration.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <template v-if="isShoutcast">
                    <b-form-group class="col-md-6" label-for="edit_form_authhash">
                        <template #label>
                            <translate key="lang_edit_form_authhash">YP Directory Authorization Hash</translate>
                        </template>
                        <template #description>{{ langAuthhashDesc }}</template>
                        <b-form-input type="text" id="edit_form_authhash" v-model="form.authhash.$model"
                                      :state="form.authhash.$dirty ? !form.authhash.$error : null"></b-form-input>
                        <b-form-invalid-feedback>
                            <translate key="lang_error_required">This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>
                </template>
                <template v-if="isIcecast">
                    <b-form-group class="col-md-6" label-for="edit_form_fallback_mount">
                        <template #label>
                            <translate key="lang_edit_form_fallback_mount">Fallback Mount</translate>
                        </template>
                        <template #description>
                            <translate key="lang_edit_form_fallback_mount_desc">If this mount point is not playing audio, listeners will automatically be redirected to this mount point. The default is /error.mp3, a repeating error message.</translate>
                        </template>
                        <b-form-input type="text" id="edit_form_fallback_mount" v-model="form.fallback_mount.$model"
                                      :state="form.fallback_mount.$dirty ? !form.fallback_mount.$error : null"></b-form-input>
                        <b-form-invalid-feedback>
                            <translate key="lang_error_required">This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>
                </template>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import { FRONTEND_ICECAST, FRONTEND_SHOUTCAST } from '../../../Entity/RadioAdapters';

export default {
    name: 'MountFormBasicInfo',
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle () {
            return this.$gettext('Basic Info');
        },
        langAuthhashDesc () {
            let text = 'If your stream is set to advertise to YP directories above, you must specify an authorization hash. You can manage authhashes <a href="%{ url }" target="_blank">on the SHOUTcast web site</a>.';
            let url = 'https://rmo.shoutcast.com';

            return this.$gettextInterpolate(this.$gettext(text), { url: url });
        },
        isIcecast () {
            return FRONTEND_ICECAST === this.stationFrontendType;
        },
        isShoutcast () {
            return FRONTEND_SHOUTCAST === this.stationFrontendType;
        }
    }
};
</script>
