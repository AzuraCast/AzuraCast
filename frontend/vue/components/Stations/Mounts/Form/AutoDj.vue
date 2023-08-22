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

            <form-group-multi-check
                v-if="formatSupportsBitrateOptions"
                id="edit_form_autodj_bitrate"
                class="col-md-6"
                :field="v$.autodj_bitrate"
                :options="bitrateOptions"
                stacked
                radio
                :label="$gettext('AutoDJ Bitrate (kbps)')"
            />
        </div>
    </tab>
</template>

<script setup>
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox";
import {map} from "lodash";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import Tab from "~/components/Common/Tab.vue";

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

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        enable_autodj: {},
        autodj_format: {},
        autodj_bitrate: {},
    },
    form,
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
