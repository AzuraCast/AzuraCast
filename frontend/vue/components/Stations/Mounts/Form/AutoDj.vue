<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <div class="form-row mb-3">
                <b-wrapped-form-checkbox class="col-md-12" id="edit_form_enable_autodj" :field="form.enable_autodj">
                    <template #label>
                        {{ $gettext('Enable AutoDJ') }}
                    </template>
                    <template #description>
                        {{ $gettext('If enabled, the AutoDJ will automatically play music to this mount point.') }}
                    </template>
                </b-wrapped-form-checkbox>
            </div>

            <div class="form-row" v-if="form.enable_autodj.$model">
                <b-wrapped-form-group class="col-md-6" id="edit_form_autodj_format" :field="form.autodj_format">
                    <template #label>
                        {{ $gettext('AutoDJ Format') }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group
                            stacked
                            :id="props.id"
                            :state="props.state"
                            v-model="props.field.$model"
                            :options="formatOptions"
                        ></b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
                <b-wrapped-form-group class="col-md-6" id="edit_form_autodj_bitrate" :field="form.autodj_bitrate"
                                      v-if="formatSupportsBitrateOptions">
                    <template #label>
                        {{ $gettext('AutoDJ Bitrate (kbps)') }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group
                            stacked
                            :id="props.id"
                            :state="props.state"
                            v-model="props.field.$model"
                            :options="bitrateOptions"
                        ></b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script>

import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'MountFormAutoDj',
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
        bitrateOptions () {
            let options = [];
            [32, 48, 64, 96, 128, 192, 256, 320].forEach((val) => {
                options.push({
                    value: val,
                    text: val
                });
            });
            return options;
        },
        formatSupportsBitrateOptions () {
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
