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

            <bitrate-options
                v-if="formatSupportsBitrateOptions"
                id="edit_form_autodj_bitrate"
                class="col-md-6"
                :max-bitrate="maxBitrate"
                :field="v$.autodj_bitrate"
                :label="$gettext('AutoDJ Bitrate (kbps)')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import Tab from "~/components/Common/Tab.vue";
import BitrateOptions from "~/components/Common/BitrateOptions.vue";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";
import {FrontendAdapter} from "~/entities/RadioAdapters.ts";

interface MountAutoDjFormProps extends FormTabProps {
    stationFrontendType: FrontendAdapter
}

const props = defineProps<MountAutoDjFormProps>();
const emit = defineEmits<FormTabEmits>();

const {maxBitrate} = useAzuraCastStation();

const {form, v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        enable_autodj: {},
        autodj_format: {},
        autodj_bitrate: {},
    },
    {
        enable_autodj: true,
        autodj_format: 'mp3',
        autodj_bitrate: 128,
    }
);

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

const formatSupportsBitrateOptions = computed(() => {
    return (form.value.autodj_format !== 'flac');
});
</script>
