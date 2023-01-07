<template>
    <b-tab :title="$gettext('Advanced')">
        <b-form-group>
            <div class="form-row mb-3">
                <b-wrapped-form-group
                    id="edit_form_custom_listen_url"
                    class="col-md-12"
                    :field="form.custom_listen_url"
                    advanced
                >
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
            <div
                v-if="isIcecast"
                class="form-row"
            >
                <b-wrapped-form-group
                    id="edit_form_frontend_config"
                    class="col-md-12"
                    :field="form.frontend_config"
                    input-type="textarea"
                    advanced
                    :input-attrs="{class: 'text-preformatted', spellcheck: 'false', 'max-rows': 25, rows: 5}"
                >
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

<script setup>
import {FRONTEND_ICECAST} from '~/components/Entity/RadioAdapters';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {computed} from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    stationFrontendType: {
        type: String,
        required: true
    }
});

const isIcecast = computed(() => {
    return FRONTEND_ICECAST === props.stationFrontendType;
});
</script>
