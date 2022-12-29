<template>
    <b-tab
        :title="$gettext('Basic Info')"
        active
    >
        <b-form-group>
            <div class="form-row mb-3">
                <b-wrapped-form-group
                    id="edit_form_name"
                    class="col-md-12"
                    :field="form.name"
                >
                    <template #label>
                        {{ $gettext('Programmatic Name') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('A name for this stream that will be used internally in code. Should only contain letters, numbers, and underscores (i.e. "stream_lofi").')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_format"
                    class="col-md-6"
                    :field="form.format"
                >
                    <template #label>
                        {{ $gettext('Audio Format') }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group
                            :id="props.id"
                            v-model="props.field.$model"
                            stacked
                            :state="props.state"
                            :options="formatOptions"
                        />
                    </template>
                </b-wrapped-form-group>
                <b-wrapped-form-group
                    id="edit_form_bitrate"
                    class="col-md-6"
                    :field="form.bitrate"
                >
                    <template #label>
                        {{ $gettext('Audio Bitrate (kbps)') }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group
                            :id="props.id"
                            v-model="props.field.$model"
                            stacked
                            :state="props.state"
                            :options="bitrateOptions"
                        />
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {map} from "lodash";

const props = defineProps({
    form: Object,
    stationFrontendType: String
});

const formatOptions = [
    {
        value: 'aac',
        text: 'AAC'
    }
];

const bitrateOptions = map(
    [32, 48, 64, 96, 128, 192, 256, 320],
    (val) => {
        return {
            value: val,
            text: val
        }
    },
);
</script>
