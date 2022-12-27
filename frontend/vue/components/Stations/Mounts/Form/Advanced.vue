<template>
    <b-tab :title="$gettext('Advanced')">
        <b-form-group>
            <div class="form-row mb-3">
                <b-wrapped-form-group class="col-md-12" id="edit_form_custom_listen_url"
                                      :field="form.custom_listen_url" advanced>
                    <template #label>
                        {{ $gettext('Mount Point URL') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('You can set a custom URL for this stream that AzuraCast will use when referring to it. Leave empty to use the default value.')
                        }}
                    </template>
                </b-wrapped-form-group>
            </div>
            <div class="form-row" v-if="isIcecast">
                <b-wrapped-form-group class="col-md-12" id="edit_form_frontend_config" :field="form.frontend_config"
                                      input-type="textarea" advanced
                                      :input-attrs="{class: 'text-preformatted', spellcheck: 'false', 'max-rows': 25, rows: 5}">
                    <template #label>
                        {{ $gettext('Custom Frontend Configuration') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('You can include any special mount point settings here, in either JSON { key: \'value\' } format or XML &lt;key&gt;value&lt;/key&gt;')
                        }}
                    </template>
                </b-wrapped-form-group>
            </div>
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
        isIcecast() {
            return FRONTEND_ICECAST === this.stationFrontendType;
        }
    }
};
</script>
