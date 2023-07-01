<template>
    <o-tab-item :label="$gettext('Advanced')">
        <b-form-group>
            <div class="row g-3 mb-3">
                <form-group-field
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
                </form-group-field>
            </div>
            <div
                v-if="isIcecast"
                class="row g-3"
            >
                <form-group-field
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
                </form-group-field>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import {FRONTEND_ICECAST} from '~/components/Entity/RadioAdapters';
import FormGroupField from "~/components/Form/FormGroupField";
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
