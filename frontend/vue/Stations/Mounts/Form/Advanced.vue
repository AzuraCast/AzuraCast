<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row class="mb-3">

                <b-wrapped-form-group class="col-md-12" id="edit_form_custom_listen_url"
                                      :field="form.custom_listen_url">
                    <template #label>
                        <translate key="lang_edit_form_custom_listen_url">Mount Point URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_custom_listen_url_desc">You can set a custom URL for this stream that AzuraCast will use when referring to it. Leave empty to use the default value.</translate>
                    </template>
                </b-wrapped-form-group>

            </b-row>
            <b-row v-if="isIcecast">

                <b-wrapped-form-group class="col-md-12" id="edit_form_frontend_config" :field="form.frontend_config">
                    <template #label>
                        <translate key="lang_edit_form_frontend_config">Custom Frontend Configuration</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_frontend_config_desc">You can include any special mount point settings here, in either JSON { key: 'value' } format or XML &lt;key&gt;value&lt;/key&gt;</translate>
                    </template>
                    <template #default="props">
                        <b-textarea :id="props.id" class="text-preformatted" v-model="props.field.$model"
                                    :state="props.state">
                        </b-textarea>
                    </template>
                </b-wrapped-form-group>

            </b-row>

        </b-form-group>
    </b-tab>
</template>

<script>
import {FRONTEND_ICECAST} from '../../../Entity/RadioAdapters';
import BWrappedFormGroup from "../../../Form/BWrappedFormGroup";

export default {
    name: 'MountFormAdvanced',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Advanced');
        },
        isIcecast() {
            return FRONTEND_ICECAST === this.stationFrontendType;
        }
    }
};
</script>
