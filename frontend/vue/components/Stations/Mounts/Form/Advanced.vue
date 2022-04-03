<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-form-row class="mb-3">

                <b-wrapped-form-group class="col-md-12" id="edit_form_custom_listen_url"
                                      :field="form.custom_listen_url" advanced>
                    <template #label="{lang}">
                        <translate :key="lang">Mount Point URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">You can set a custom URL for this stream that AzuraCast will use when referring to it. Leave empty to use the default value.</translate>
                    </template>
                </b-wrapped-form-group>

            </b-form-row>
            <b-form-row v-if="isIcecast">

                <b-wrapped-form-group class="col-md-12" id="edit_form_frontend_config" :field="form.frontend_config"
                                      input-type="textarea" advanced
                                      :input-attrs="{class: 'text-preformatted', spellcheck: 'false', 'max-rows': 25, rows: 5}">
                    <template #label="{lang}">
                        <translate :key="lang">Custom Frontend Configuration</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">You can include any special mount point settings here, in either JSON { key: 'value' } format or XML &lt;key&gt;value&lt;/key&gt;</translate>
                    </template>
                </b-wrapped-form-group>

            </b-form-row>

        </b-form-group>
    </b-tab>
</template>

<script>
import {FRONTEND_ICECAST} from '~/components/Entity/RadioAdapters';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

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
