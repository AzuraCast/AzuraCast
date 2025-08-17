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
import {useValidatedFormTab} from "~/functions/useValidatedFormTab.ts";
import {required} from "@regle/rules";
import Tab from "~/components/Common/Tab.vue";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";
import {ApiGenericForm, HlsStreamProfiles} from "~/entities/ApiInterfaces.ts";

const form = defineModel<ApiGenericForm>('form', {required: true});

const {maxBitrate} = useAzuraCastStation();

const {v$, tabClass} = useValidatedFormTab(
    form,
    {
        name: {required},
        format: {required},
        bitrate: {required}
    },
    {
        name: null,
        format: HlsStreamProfiles.AacLowComplexity,
        bitrate: 128
    }
);

const formatOptions = [
    {
        value: HlsStreamProfiles.AacLowComplexity,
        text: 'AAC Low Complexity (Default)',
    },
    {
        value: HlsStreamProfiles.AacHighEfficiencyV1,
        text: 'AAC High Efficiency V1 (HE-AAC)'
    },
    {
        value: HlsStreamProfiles.AacHighEfficiencyV2,
        text: 'AAC High Efficiency V2 (HE-AACv2)'
    },
    {
        value: HlsStreamProfiles.AacEnhancedLowDelay,
        text: 'AAC Low Delay (LD)'
    },
    {
        value: HlsStreamProfiles.AacEnhancedLowDelay,
        text: 'AAC Enhanced Low Delay (ELD)'
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
