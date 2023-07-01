<template>
    <o-tab-item :label="$gettext('AutoDJ')">
        <b-form-group>
            <div class="row g-3 mb-3">
                <form-group-checkbox
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
                </form-group-checkbox>
            </div>

            <div
                v-if="form.enable_autodj.$model"
                class="row g-3"
            >
                <form-group-field
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
                </form-group-field>
                <form-group-field
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
                </form-group-field>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox";
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
