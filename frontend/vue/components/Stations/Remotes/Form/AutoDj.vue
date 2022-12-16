<template>
    <b-tab :title="langTabTitle">
        <b-form-row class="mb-3">

            <b-wrapped-form-checkbox class="col-md-12" id="edit_form_enable_autodj" :field="form.enable_autodj">
                <template #label="{lang}">
                    {{ $gettext('Broadcast AutoDJ to Remote Station') }}
                </template>
                <template #description="{lang}">
                    {{
                        $gettext('If enabled, the AutoDJ on this installation will automatically play music to this mount point.')
                    }}
                </template>
            </b-wrapped-form-checkbox>

        </b-form-row>

        <b-form-row v-if="form.enable_autodj.$model">

            <b-wrapped-form-group class="col-md-6" id="edit_form_autodj_format" :field="form.autodj_format">
                <template #label="{lang}">
                    {{ $gettext('AutoDJ Format') }}
                </template>
                <template #default="props">
                    <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                        :options="formatOptions"></b-form-radio-group>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_autodj_bitrate" :field="form.autodj_bitrate"
                                  v-if="formatSupportsBitrateOptions">
                <template #label="{lang}">
                    {{ $gettext('AutoDJ Bitrate (kbps)') }}
                </template>
                <template #default="props">
                    <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                        :options="bitrateOptions"></b-form-radio-group>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_source_port" :field="form.source_port">
                <template #label="{lang}">
                    {{ $gettext('Remote Station Source Port') }}
                </template>
                <template #description="{lang}">
                    {{
                        $gettext('If the port you broadcast to is different from the one you listed in the URL above, specify the source port here.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_source_mount" :field="form.source_mount">
                <template #label="{lang}">
                    {{ $gettext('Remote Station Source Mountpoint/SID') }}
                </template>
                <template #description="{lang}">
                    {{
                        $gettext('If the mountpoint (i.e. /radio.mp3) or Shoutcast SID (i.e. 2) you broadcast to is different from the one listed above, specify the source mount point here.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_source_username" :field="form.source_username">
                <template #label="{lang}">
                    {{ $gettext('Remote Station Source Username') }}
                </template>
                <template #description="{lang}">
                    {{
                        $gettext('If you are broadcasting using AutoDJ, enter the source username here. This may be blank.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_source_password" :field="form.source_password">
                <template #label="{lang}">
                    {{ $gettext('Remote Station Source Password') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('If you are broadcasting using AutoDJ, enter the source password here.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-checkbox class="col-md-6" id="edit_form_is_public" :field="form.is_public">
                <template #label="{lang}">
                    {{ $gettext('Publish to "Yellow Pages" Directories') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('Enable to advertise this relay on "Yellow Pages" public radio directories.') }}
                </template>
            </b-wrapped-form-checkbox>

        </b-form-row>
    </b-tab>
</template>

<script>

import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'RemoteFormAutoDj',
    components: {BWrappedFormCheckbox, BWrappedFormGroup},
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle() {
            return this.$gettext('AutoDJ');
        },
        formatOptions() {
            return [
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
        },
        bitrateOptions() {
            let options = [];
            [32, 48, 64, 96, 128, 192, 256, 320].forEach((val) => {
                options.push({
                    value: val,
                    text: val
                });
            });
            return options;
        },
        formatSupportsBitrateOptions() {
            switch (this.form.autodj_format.$model) {
                case 'flac':
                    return false;

                case 'mp3':
                case 'ogg':
                case 'opus':
                case 'aac':
                default:
                    return true;
            }
        }
    }
};
</script>
