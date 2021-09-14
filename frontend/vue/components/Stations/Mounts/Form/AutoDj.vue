<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row class="mb-3">
                <b-wrapped-form-group class="col-md-12" id="edit_form_enable_autodj" :field="form.enable_autodj">
                    <template #description>
                        <translate key="lang_edit_form_enable_autodj_desc">If enabled, the AutoDJ will automatically play music to this mount point.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_edit_form_enable_autodj">Enable AutoDJ</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>

            <b-row v-if="form.enable_autodj.$model">
                <b-wrapped-form-group class="col-md-6" id="edit_form_autodj_format" :field="form.autodj_format">
                    <template #label>
                        <translate key="lang_edit_form_autodj_format">AutoDJ Format</translate>
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
                        <translate key="lang_edit_form_autodj_bitrate">AutoDJ Bitrate (kbps)</translate>
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
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>

import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'MountFormAutoDj',
    components: {BWrappedFormGroup},
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
