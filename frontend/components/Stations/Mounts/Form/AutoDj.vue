<template>
    <tab
        :label="$gettext('AutoDJ')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-checkbox
                id="edit_form_enable_autodj"
                class="col-md-12"
                :field="v$.enable_autodj"
                :label="$gettext('Enable AutoDJ')"
                :description="$gettext('If enabled, the AutoDJ will automatically play music to this mount point.')"
            />
        </div>

        <div
            v-if="form.enable_autodj"
            class="row g-3"
        >
            <form-group-multi-check
                id="edit_form_autodj_format"
                class="col-md-6"
                :field="v$.autodj_format"
                :options="formatOptions"
                stacked
                radio
                :label="$gettext('AutoDJ Format')"
            />

            <form-group-field
                v-if="formatSupportsBitrateOptions"
                id="edit_form_autodj_bitrate"
                class="col-md-6"
                :label="$gettext('AutoDJ Bitrate (kbps)')"
                :field="v$.autodj_bitrate"
            >
                <template #default="{id, model, fieldClass}">
                    <bitrate-options
                        :id="id"
                        v-model="model.$model"
                        :class="fieldClass"
                        :max-bitrate="maxBitrate"
                    />
                </template>
            </form-group-field>
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useValidatedFormTab} from "~/functions/useValidatedFormTab.ts";
import Tab from "~/components/Common/Tab.vue";
import BitrateOptions from "~/components/Common/BitrateOptions.vue";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";
import {ApiGenericForm, FrontendAdapters, StreamFormats} from "~/entities/ApiInterfaces.ts";
import FormGroupField from "~/components/Form/FormGroupField.vue";

defineProps<{
    stationFrontendType: FrontendAdapters
}>();

const form = defineModel<ApiGenericForm>('form', {required: true});

const {maxBitrate} = useAzuraCastStation();

const {v$, tabClass} = useValidatedFormTab(
    form,
    {
        enable_autodj: {},
        autodj_format: {},
        autodj_bitrate: {},
    },
    {
        enable_autodj: true,
        autodj_format: StreamFormats.Mp3,
        autodj_bitrate: 128,
    }
);

const formatOptions = [
    {
        value: StreamFormats.Mp3,
        text: 'MP3'
    },
    {
        value: StreamFormats.Ogg,
        text: 'OGG Vorbis'
    },
    {
        value: StreamFormats.Opus,
        text: 'OGG Opus'
    },
    {
        value: StreamFormats.Aac,
        text: 'AAC+ (MPEG4 HE-AAC v2)'
    },
    {
        value: StreamFormats.Flac,
        text: 'FLAC (OGG FLAC)'
    }
];

const formatSupportsBitrateOptions = computed(() => {
    return (form.value.autodj_format !== 'flac');
});
</script>
