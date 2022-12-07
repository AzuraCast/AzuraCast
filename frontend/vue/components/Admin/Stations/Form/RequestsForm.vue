<template>
    <div>
        <b-form-fieldset v-if="isBackendEnabled">
            <b-form-fieldset>
                <template #label>
                    <translate key="lang_hdr_song_requests">Song Requests</translate>
                </template>
                <template #description>
                    <translate key="lang_song_requests_desc">Some stream licensing providers may have specific rules
                        regarding song requests. Check your local regulations for more information.
                    </translate>
                </template>

                <b-form-fieldset>
                    <b-form-row>
                        <b-wrapped-form-checkbox class="col-md-12" id="edit_form_enable_requests"
                                                 :field="form.enable_requests">
                            <template #label="{lang}">
                                <translate :key="lang">Allow Song Requests</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">Enable listeners to request a song for play on your station. Only
                                    songs that are already in your playlists are requestable.
                                </translate>
                            </template>
                        </b-wrapped-form-checkbox>
                    </b-form-row>
                </b-form-fieldset>

                <b-form-fieldset v-if="form.enable_requests.$model">
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-6" id="edit_form_request_delay"
                                              :field="form.request_delay" input-type="number"
                                              :input-attrs="{ min: '0', max: '1440' }">
                            <template #label="{lang}">
                                <translate :key="lang">Request Minimum Delay (Minutes)</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">If requests are enabled, this specifies the minimum delay (in
                                    minutes) between a request being submitted and being played. If set to zero, a minor
                                    delay of 15 seconds is applied to prevent request floods.
                                </translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="edit_form_request_threshold"
                                              :field="form.request_threshold" input-type="number"
                                              :input-attrs="{ min: '0', max: '1440' }">
                            <template #label="{lang}">
                                <translate :key="lang">Request Last Played Threshold (Minutes)</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">This specifies the minimum time (in minutes) between a song
                                    playing on the radio and being available to request again. Set to 0 for no
                                    threshold.
                                </translate>
                            </template>
                        </b-wrapped-form-group>
                    </b-form-row>
                </b-form-fieldset>
            </b-form-fieldset>
        </b-form-fieldset>
        <backend-disabled v-else></backend-disabled>
    </div>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {BACKEND_NONE} from "~/components/Entity/RadioAdapters";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import BFormMarkup from "~/components/Form/BFormMarkup";
import BackendDisabled from "./Common/BackendDisabled.vue";

export default {
    name: 'AdminStationsRequestsForm',
    components: {BackendDisabled, BFormMarkup, BWrappedFormCheckbox, BWrappedFormGroup, BFormFieldset},
    props: {
        form: Object,
        station: Object,
        showAdvanced: {
            type: Boolean,
            default: true
        },
    },
    computed: {
        isBackendEnabled() {
            return this.form.backend_type.$model !== BACKEND_NONE;
        },
    }
}
</script>
