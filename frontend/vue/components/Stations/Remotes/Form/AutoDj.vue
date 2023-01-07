<template>
    <b-tab :title="$gettext('AutoDJ')">
        <div class="form-row mb-3">
            <b-wrapped-form-checkbox
                id="edit_form_enable_autodj"
                class="col-md-12"
                :field="form.enable_autodj"
            >
                <template #label>
                    {{ $gettext('Broadcast AutoDJ to Remote Station') }}
                </template>
                <template #description>
                    {{
                        $gettext('If enabled, the AutoDJ on this installation will automatically play music to this mount point.')
                    }}
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
                        :options="bitrateOptions"
                    />
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_source_port"
                class="col-md-6"
                :field="form.source_port"
            >
                <template #label>
                    {{ $gettext('Remote Station Source Port') }}
                </template>
                <template #description>
                    {{
                        $gettext('If the port you broadcast to is different from the one you listed in the URL above, specify the source port here.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_source_mount"
                class="col-md-6"
                :field="form.source_mount"
            >
                <template #label>
                    {{ $gettext('Remote Station Source Mountpoint/SID') }}
                </template>
                <template #description>
                    {{
                        $gettext('If the mountpoint (i.e. /radio.mp3) or Shoutcast SID (i.e. 2) you broadcast to is different from the one listed above, specify the source mount point here.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_source_username"
                class="col-md-6"
                :field="form.source_username"
            >
                <template #label>
                    {{ $gettext('Remote Station Source Username') }}
                </template>
                <template #description>
                    {{
                        $gettext('If you are broadcasting using AutoDJ, enter the source username here. This may be blank.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_source_password"
                class="col-md-6"
                :field="form.source_password"
            >
                <template #label>
                    {{ $gettext('Remote Station Source Password') }}
                </template>
                <template #description>
                    {{ $gettext('If you are broadcasting using AutoDJ, enter the source password here.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-checkbox
                id="edit_form_is_public"
                class="col-md-6"
                :field="form.is_public"
            >
                <template #label>
                    {{ $gettext('Publish to "Yellow Pages" Directories') }}
                </template>
                <template #description>
                    {{ $gettext('Enable to advertise this relay on "Yellow Pages" public radio directories.') }}
                </template>
            </b-wrapped-form-checkbox>
        </div>
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
    return props.form.autodj_format.$model !== 'flac';
});
</script>
