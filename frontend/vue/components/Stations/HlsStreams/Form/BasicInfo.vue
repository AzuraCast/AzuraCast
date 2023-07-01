<template>
    <o-tab-item
        :label="$gettext('Basic Info')"
        active
    >
        <b-form-group>
            <div class="row g-3 mb-3">
                <form-group-field
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
                </form-group-field>

                <form-group-field
                    id="edit_form_format"
                    class="col-md-6"
                    :field="form.format"
                >
                    <template #label>
                        {{ $gettext('Audio Format') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :state="slotProps.state"
                            :options="formatOptions"
                        />
                    </template>
                </form-group-field>
                <form-group-field
                    id="edit_form_bitrate"
                    class="col-md-6"
                    :field="form.bitrate"
                >
                    <template #label>
                        {{ $gettext('Audio Bitrate (kbps)') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :state="slotProps.state"
                            :options="bitrateOptions"
                        />
                    </template>
                </form-group-field>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import {map} from "lodash";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
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
