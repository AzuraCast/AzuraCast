<template>
    <b-tab :title="$gettext('AutoDJ')">
        <b-form-group>
            <div class="form-row mb-3">
                <b-wrapped-form-checkbox
                    id="edit_form_enable_autodj"
                    class="col-md-12"
                    :field="form.enable_autodj"
                >
                    <template #label>
                        {{ $gettext('Enable AutoDJ') }}
                    </template>
                    <template #description>
                        {{ $gettext('If enabled, the AutoDJ will automatically play music to this mount point.') }}
                    </template>
                </b-wrapped-form-checkbox>
            </div>

            <div
                v-if="form.enable_autodj.$model"
                class="form-row"
            >
                <b-wrapped-form-group
                    id="edit_form_autodj_format"
                    class="col-md-6"
                    :field="form.autodj_format"
                >
                    <template #label>
                        {{ $gettext('AutoDJ Format') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :state="slotProps.state"
                            :options="formatOptions"
                        />
                    </template>
                </b-wrapped-form-group>
                <b-wrapped-form-group
                    v-if="formatSupportsBitrateOptions"
                    id="edit_form_autodj_bitrate"
                    class="col-md-6"
                    :field="form.autodj_bitrate"
                >
                    <template #label>
                        {{ $gettext('AutoDJ Bitrate (kbps)') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :state="slotProps.state"
                            :options="bitrateOptions"
                        />
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import {map} from "lodash";
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

const formatOptions = [
    {
        value: 'mp3',
        text: 'MP3'
    },
    {
        value: 'ogg',
        text: 'OGG Vorbis'
    },
    {
        value: 'opus',
        text: 'OGG Opus'
    },
    {
        value: 'aac',
        text: 'AAC+ (MPEG4 HE-AAC v2)'
    },
    {
        value: 'flac',
        text: 'FLAC (OGG FLAC)'
    }
];

const bitrateOptions = map(
    [32, 48, 64, 96, 128, 192, 256, 320],
    (val) => {
        return {
            value: val,
            text: val
        };
    }
);

const formatSupportsBitrateOptions = computed(() => {
    return (props.form.autodj_format.$model !== 'flac');
});
</script>
