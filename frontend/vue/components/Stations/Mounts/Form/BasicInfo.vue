<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>

            <b-form-row class="mb-3">

                <b-wrapped-form-group class="col-md-6" id="edit_form_name" :field="form.name">
                    <template #label="{lang}">
                        <translate :key="lang">Mount Point URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">This name should always begin with a slash (/), and must be a valid URL, such as /autodj.mp3</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_display_name" :field="form.display_name">
                    <template #label="{lang}">
                        <translate :key="lang">Display Name</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The display name assigned to this mount point when viewing it on administrative or public pages. Leave blank to automatically generate one.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox class="col-md-6" id="edit_form_is_visible_on_public_pages"
                                         :field="form.is_visible_on_public_pages">
                    <template #label="{lang}">
                        <translate :key="lang">Show on Public Pages</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Enable to allow listeners to select this mount point on this station's public pages.</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox class="col-md-6" id="edit_form_is_default" :field="form.is_default">
                    <template #label="{lang}">
                        <translate :key="lang">Set as Default Mount Point</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">If this mount is the default, it will be played on the radio preview and the public radio page in this system.</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-6" id="edit_form_relay_url" :field="form.relay_url">
                    <template #label="{lang}">
                        <translate :key="lang">Relay Stream URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Enter the full URL of another stream to relay its broadcast through this mount point.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox class="col-md-6" id="edit_form_is_public" :field="form.is_public">
                    <template #label="{lang}">
                        <translate :key="lang">Publish to "Yellow Pages" Directories</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Enable to advertise this mount point on "Yellow Pages" public radio directories.</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-6" id="edit_form_max_listener_duration"
                                      :field="form.max_listener_duration" input-type="number"
                                      :input-attrs="{min: '0', max: '2147483647'}">
                    <template #label="{lang}">
                        <translate :key="lang">Max Listener Duration</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Set the length of time (seconds) a listener will stay connected to the stream. If set to 0, listeners can stay connected infinitely.</translate>
                    </template>
                </b-wrapped-form-group>

                <template v-if="isShoutcast">
                    <b-wrapped-form-group class="col-md-6" id="edit_form_authhash" :field="form.authhash">
                        <template #label="{lang}">
                            <translate :key="lang">YP Directory Authorization Hash</translate>
                        </template>
                        <template #description><span v-html="langAuthhashDesc"></span></template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="isIcecast">
                    <b-wrapped-form-group class="col-md-6" id="edit_form_fallback_mount" :field="form.fallback_mount">
                        <template #label="{lang}">
                            <translate :key="lang">Fallback Mount</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">If this mount point is not playing audio, listeners will automatically be redirected to this mount point. The default is /error.mp3, a repeating error message.</translate>
                        </template>
                    </b-wrapped-form-group>
                </template>
            </b-form-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'MountFormBasicInfo',
    components: {BWrappedFormCheckbox, BWrappedFormGroup},
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Basic Info');
        },
        langAuthhashDesc() {
            let text = 'If your stream is set to advertise to YP directories above, you must specify an authorization hash. You can manage authhashes <a href="%{ url }" target="_blank">on the Shoutcast web site</a>.';
            let url = 'https://radiomanager.shoutcast.com/';

            return this.$gettextInterpolate(this.$gettext(text), {url: url});
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
