<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-form-row class="mb-3">
                <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
                    <template #label="{lang}">
                        <translate :key="lang">Programmatic Name</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">A name for this stream that will be used internally in code. Should only contain letters, numbers, and underscores (i.e. "stream_lofi").</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_format" :field="form.format">
                    <template #label="{lang}">
                        <translate :key="lang">Audio Format</translate>
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
                <b-wrapped-form-group class="col-md-6" id="edit_form_bitrate" :field="form.bitrate">
                    <template #label="{lang}">
                        <translate :key="lang">Audio Bitrate (kbps)</translate>
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
            </b-form-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'HlsStreamFormBasicInfo',
    components: {BWrappedFormCheckbox, BWrappedFormGroup},
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Basic Info');
        },
        formatOptions() {
            return [
                {
                    value: 'aac',
                    text: 'AAC'
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
    }
};
</script>
