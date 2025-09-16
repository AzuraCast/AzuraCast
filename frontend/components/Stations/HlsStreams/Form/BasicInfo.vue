<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="r$.name"
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
                :field="r$.format"
                :options="formatOptions"
                stacked
                radio
                :label="$gettext('Audio Format')"
            />

            <form-group-multi-check
                id="edit_form_bitrate"
                class="col-md-6"
                :field="r$.bitrate"
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
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Tab from "~/components/Common/Tab.vue";
import {HlsStreamProfiles} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {computed} from "vue";
import {useStationsHlsStreamsForm} from "~/components/Stations/HlsStreams/Form/form.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";

const {r$} = storeToRefs(useStationsHlsStreamsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.basicInfoTab));

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
    }
];

const stationData = useStationData();
const {maxBitrate} = toRefs(stationData);

const defaultBitrateOptions = [32, 48, 64, 96, 128, 192, 256, 320];

const bitrateOptions = computed(() =>
    defaultBitrateOptions.filter((bitrate) => maxBitrate.value === 0 || bitrate <= maxBitrate.value)
        .map((val) => ({
            value: val,
            text: String(val)
        }))
);
</script>
