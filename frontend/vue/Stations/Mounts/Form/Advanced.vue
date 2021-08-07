<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row class="mb-3">

                <b-form-group class="col-md-12" label-for="edit_form_custom_listen_url">
                    <template #label>
                        <translate key="lang_edit_form_custom_listen_url">Mount Point URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_custom_listen_url_desc">You can set a custom URL for this stream that AzuraCast will use when referring to it. Leave empty to use the default value.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_custom_listen_url" v-model="form.custom_listen_url.$model"
                                  :state="form.custom_listen_url.$dirty ? !form.custom_listen_url.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

            </b-row>
            <b-row v-if="isIcecast">

                <b-form-group class="col-md-12" label-for="edit_form_frontend_config">
                    <template #label>
                        <translate key="lang_edit_form_frontend_config">Custom Frontend Configuration</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_frontend_config_desc">You can include any special mount point settings here, in either JSON { key: 'value' } format or XML &lt;key&gt;value&lt;/key&gt;</translate>
                    </template>
                    <b-textarea id="edit_form_frontend_config" class="text-preformatted" v-model="form.frontend_config.$model"
                                :state="form.frontend_config.$dirty ? !form.frontend_config.$error : null">
                    </b-textarea>
                </b-form-group>

            </b-row>

        </b-form-group>
    </b-tab>
</template>

<script>
import { FRONTEND_ICECAST } from '../../../Entity/RadioAdapters';

export default {
    name: 'MountFormAdvanced',
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle () {
            return this.$gettext('Advanced');
        },
        isIcecast () {
            return FRONTEND_ICECAST === this.stationFrontendType;
        }
    }
};
</script>
