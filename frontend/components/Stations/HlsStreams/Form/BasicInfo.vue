<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="v$.name"
                :label="$gettext('Programmatic Name')"
            >
                <template #description>
                    {{
                        $gettext('A name for this stream that will be used internally in code. Should only contain letters, numbers, and underscores (i.e. "stream_lofi").')
                    }}
                </template>
            </form-group-field>

            <form-group-multi-check
                id="edit_form_format"
                class="col-md-6"
                :field="v$.format"
                :options="formatOptions"
                stacked
                radio
                :label="$gettext('Audio Format')"
            />

            <form-group-multi-check
                id="edit_form_bitrate"
                class="col-md-6"
                :field="v$.bitrate"
                :options="bitrateOptions"
                stacked
                radio
                :label="$gettext('Audio Bitrate (kbps)')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {map} from "lodash";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";

const props = defineProps<FormTabProps>();
const emit = defineEmits<FormTabEmits>();

const {maxBitrate} = useAzuraCastStation();

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        name: {required},
        format: {required},
        bitrate: {required}
    },
    {
        name: null,
        format: 'aac',
        bitrate: 128
    }
);

const formatOptions = [
    {
        value: 'aac',
        text: 'AAC'
    }
];

const bitrateOptions = map(
    [32, 48, 64, 96, 128, 192, 256, 320].filter((bitrate) => maxBitrate === 0 || bitrate <= maxBitrate),
    (val) => {
        return {
            value: val,
            text: String(val)
        }
    },
);
</script>
