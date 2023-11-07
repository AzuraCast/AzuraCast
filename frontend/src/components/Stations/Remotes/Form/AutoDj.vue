<template>
    <tabs
        :label="$gettext('AutoDJ')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-checkbox
                id="edit_form_enable_autodj"
                class="col-md-12"
                :field="v$.enable_autodj"
                :label="$gettext('Broadcast AutoDJ to Remote Station')"
                :description="$gettext('If enabled, the AutoDJ on this installation will automatically play music to this mount point.')"
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

            <form-group-field
                id="edit_form_source_port"
                class="col-md-6"
                :field="v$.source_port"
                :label="$gettext('Remote Station Source Port')"
                :description="$gettext('If the port you broadcast to is different from the stream URL, specify the source port here.')"
            />

            <form-group-field
                id="edit_form_source_mount"
                class="col-md-6"
                :field="v$.source_mount"
                :label="$gettext('Remote Station Source Mountpoint/SID')"
                :description="$gettext('If the mountpoint (i.e. /radio.mp3) or Shoutcast SID (i.e. 2) you broadcast to is different from the stream URL, specify the source mount point here.')"
            />

            <form-group-field
                id="edit_form_source_username"
                class="col-md-6"
                :field="v$.source_username"
                :label="$gettext('Remote Station Source Username')"
                :description="$gettext('If you are broadcasting using AutoDJ, enter the source username here. This may be blank.')"
            />

            <form-group-field
                id="edit_form_source_password"
                class="col-md-6"
                :field="v$.source_password"
                :label="$gettext('Remote Station Source Password')"
                :description="$gettext('If you are broadcasting using AutoDJ, enter the source password here.')"
            />

            <form-group-checkbox
                id="edit_form_is_public"
                class="col-md-6"
                :field="v$.is_public"
            >
                <template #label>
                    {{ $gettext('Publish to "Yellow Pages" Directories') }}
                </template>
                <template #description>
                    {{ $gettext('Enable to advertise this relay on "Yellow Pages" public radio directories.') }}
                </template>
            </form-group-checkbox>
        </div>
    </tabs>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {map} from "lodash";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps({
    form: {
        type: Object,
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
        source_port: {},
        source_mount: {},
        source_username: {},
        source_password: {},
        is_public: {},
    },
    form,
    {
        enable_autodj: false,
        autodj_format: 'mp3',
        autodj_bitrate: 128,
        source_port: null,
        source_mount: null,
        source_username: null,
        source_password: null,
        is_public: false
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
    return form.value?.autodj_format !== 'flac';
});
</script>
